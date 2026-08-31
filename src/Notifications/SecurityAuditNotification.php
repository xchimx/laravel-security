<?php

namespace Xchimx\LaravelSecurity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Support\Facades\Route;
use Xchimx\LaravelSecurity\Models\SecurityAudit;
use Xchimx\LaravelSecurity\Support\AuditDiff;

class SecurityAuditNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, SecurityAudit>  $results
     * @param  array<string>  $channels
     * @param  array<string, AuditDiff>  $diffs  keyed by audit source
     */
    public function __construct(
        protected array $results,
        protected array $channels = ['mail', 'database', 'slack'],
        protected array $diffs = []
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $totalVulnerabilities = (int) array_sum(array_column($this->results, 'vulnerabilities_count'));
        $appName = (string) config('app.name');

        $message = (new MailMessage)
            ->error()
            ->subject(__('security::notifications.audit.subject', ['app' => $appName]))
            ->greeting(__('security::notifications.audit.greeting', ['app' => $appName]))
            ->line(__('security::notifications.audit.summary', ['count' => $totalVulnerabilities]));

        foreach ($this->results as $result) {
            if (! $result->has_issues || ! is_array($result->results)) {
                continue;
            }

            $diff = $this->diffs[$result->source] ?? null;

            if ($diff instanceof AuditDiff) {
                $message->line(__('security::notifications.audit.source_summary_new', [
                    'source' => $result->source,
                    'count' => count($diff->new),
                ]));
                $vulnerabilities = $diff->new;
            } else {
                $message->line(__('security::notifications.audit.source_summary', [
                    'source' => $result->source,
                    'count' => $result->vulnerabilities_count,
                ]));
                $vulnerabilities = $result->results;
            }

            $packages = array_slice($vulnerabilities, 0, 5);
            foreach ($packages as $vulnerability) {
                $severity = is_string($vulnerability['severity'] ?? null) ? $vulnerability['severity'] : 'unknown';
                $package = is_string($vulnerability['package'] ?? null) ? $vulnerability['package'] : 'unknown';
                $title = is_string($vulnerability['title'] ?? null) ? $vulnerability['title'] : 'unknown vulnerability';
                $message->line("- [{$severity}] {$package}: {$title}");
            }

            if (count($vulnerabilities) > 5) {
                $remaining = count($vulnerabilities) - 5;
                $message->line(__('security::notifications.audit.more', ['count' => $remaining]));
            }

            if ($diff instanceof AuditDiff && count($diff->known) > 0) {
                $message->line(__('security::notifications.audit.still_open', ['count' => count($diff->known)]));
            }
        }

        $message->action(__('security::notifications.audit.action'), $this->dashboardUrl())
            ->line(__('security::notifications.audit.footer'));

        return $message;
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $totalVulnerabilities = (int) array_sum(array_column($this->results, 'vulnerabilities_count'));
        $appName = (string) config('app.name');
        $appRoute = $this->dashboardUrl();

        $message = (new SlackMessage)
            ->text(__('security::notifications.audit.slack_text', ['app' => $appName]))
            ->headerBlock(__('security::notifications.audit.slack_header', ['app' => $appName]))
            ->sectionBlock(function (SectionBlock $block) use ($totalVulnerabilities, $appRoute) {
                $summary = __('security::notifications.audit.slack_summary', ['count' => $totalVulnerabilities]);
                $dashboardLabel = __('security::notifications.view_dashboard');
                $block->text("{$summary}\n<{$appRoute}|{$dashboardLabel}>")
                    ->markdown();
            });

        foreach ($this->results as $result) {
            if (! $result->has_issues || empty($result->results)) {
                continue;
            }

            $diff = $this->diffs[$result->source] ?? null;

            $message->dividerBlock();
            $message->sectionBlock(function (SectionBlock $block) use ($result, $diff) {
                if ($diff instanceof AuditDiff) {
                    $block->text(__('security::notifications.audit.slack_source_summary_new', [
                        'source' => ucfirst($result->source),
                        'count' => count($diff->new),
                    ]))->markdown();
                } else {
                    $block->text(__('security::notifications.audit.slack_source_summary', [
                        'source' => ucfirst($result->source),
                        'count' => $result->vulnerabilities_count,
                    ]))->markdown();
                }
            });

            $vulnerabilities = $diff instanceof AuditDiff ? $diff->new : ($result->results ?? []);

            $packages = array_slice($vulnerabilities, 0, 5);
            $vulnList = '';

            foreach ($packages as $vulnerability) {
                $severity = is_string($vulnerability['severity'] ?? null) ? $vulnerability['severity'] : 'unknown';
                $package = is_string($vulnerability['package'] ?? null) ? $vulnerability['package'] : 'unknown';
                $title = is_string($vulnerability['title'] ?? null) ? $vulnerability['title'] : 'unknown vulnerability';

                $emoji = match (strtolower($severity)) {
                    'critical', 'high' => '🔴',
                    'medium', 'moderate' => '🟠',
                    default => '🟡',
                };

                $vulnList .= "{$emoji} *[{$severity}]* `{$package}`: {$title}\n";
            }

            if (count($vulnerabilities) > 5) {
                $remaining = count($vulnerabilities) - 5;
                $vulnList .= __('security::notifications.audit.more', ['count' => $remaining]);
            }

            if ($diff instanceof AuditDiff && count($diff->known) > 0) {
                $stillOpen = __('security::notifications.audit.still_open', ['count' => count($diff->known)]);
                $vulnList .= ($vulnList !== '' ? "\n" : '').$stillOpen;
            }

            if ($vulnList !== '') {
                $message->sectionBlock(function (SectionBlock $block) use ($vulnList) {
                    $block->text($vulnList)->markdown();
                });
            }
        }

        $message->contextBlock(function ($block) {
            $block->text(__('security::notifications.audit.footer'));
        });

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $totalVulnerabilities = (int) array_sum(array_column($this->results, 'vulnerabilities_count'));

        return [
            'type' => 'security_audit',
            'app_name' => config('app.name'),
            'total_vulnerabilities' => $totalVulnerabilities,
            'results' => array_map(function (SecurityAudit $result): array {
                $diff = $this->diffs[$result->source] ?? null;

                return [
                    'source' => $result->source,
                    'vulnerabilities_count' => $result->vulnerabilities_count,
                    'packages' => $result->results,
                    'new_count' => $diff instanceof AuditDiff ? count($diff->new) : null,
                    'known_count' => $diff instanceof AuditDiff ? count($diff->known) : null,
                ];
            }, $this->results),
            'url' => $this->dashboardUrl(),
        ];
    }

    private function dashboardUrl(): string
    {
        $routeName = config('security.notifications.route');

        if (is_string($routeName) && $routeName !== '' && Route::has($routeName)) {
            return route($routeName);
        }

        return url('/');
    }
}

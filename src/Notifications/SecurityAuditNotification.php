<?php

namespace Xchimx\LaravelSecurity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Support\Facades\Route;
use Xchimx\LaravelSecurity\Models\SecurityAudit;

class SecurityAuditNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, SecurityAudit>  $results
     * @param  array<string>  $channels
     */
    public function __construct(
        protected array $results,
        protected array $channels = ['mail', 'database', 'slack']
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
            ->subject("🔒 Security Vulnerabilities Detected - {$appName}")
            ->greeting("Security Alert for {$appName}")
            ->line("We have detected **{$totalVulnerabilities} security vulnerabilities** in your application dependencies.");

        foreach ($this->results as $result) {
            if (! $result->has_issues || ! is_array($result->results)) {
                continue;
            }

            $message->line("**{$result->source}**: {$result->vulnerabilities_count} vulnerabilities found");

            // Add first 5 vulnerabilities
            $resultsData = $result->results ?? [];
            $packages = array_slice($resultsData, 0, 5);
            foreach ($packages as $pkg) {
                $severity = is_string($pkg['severity'] ?? null) ? $pkg['severity'] : 'unknown';
                $package = is_string($pkg['package'] ?? null) ? $pkg['package'] : 'unknown';
                $title = is_string($pkg['title'] ?? null) ? $pkg['title'] : 'unknown vulnerability';
                $message->line("- [{$severity}] {$package}: {$title}");
            }

            if (count($resultsData) > 5) {
                $remaining = count($resultsData) - 5;
                $message->line("... and {$remaining} more vulnerabilities");
            }
        }

        $appRoute = $this->dashboardUrl();
        $message->action('View Full Report', $appRoute)
            ->line('Please review and update the affected packages as soon as possible.');

        return $message;
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $totalVulnerabilities = (int) array_sum(array_column($this->results, 'vulnerabilities_count'));
        $appName = (string) config('app.name');
        $appRoute = $this->dashboardUrl();

        $message = (new SlackMessage)
            ->text("🔒 Security vulnerabilities detected in {$appName}")
            ->headerBlock("🔒 Security Alert - {$appName}")
            ->sectionBlock(function (SectionBlock $block) use ($totalVulnerabilities, $appRoute) {
                $block->text("We have detected *{$totalVulnerabilities} security vulnerabilities* in your application dependencies.\n<{$appRoute}|View Full Dashboard>")
                    ->markdown();
            });

        foreach ($this->results as $result) {
            if (! $result->has_issues || empty($result->results)) {
                continue;
            }

            $message->dividerBlock();
            $message->sectionBlock(function (SectionBlock $block) use ($result) {
                $sourceName = ucfirst($result->source);
                $block->text("*{$sourceName}*: {$result->vulnerabilities_count} vulnerabilities found")
                    ->markdown();
            });

            $resultsData = $result->results ?? [];
            $packages = array_slice($resultsData, 0, 5);
            $vulnList = '';

            foreach ($packages as $vuln) {
                $severity = is_string($vuln['severity'] ?? null) ? $vuln['severity'] : 'unknown';
                $package = is_string($vuln['package'] ?? null) ? $vuln['package'] : 'unknown';
                $title = is_string($vuln['title'] ?? null) ? $vuln['title'] : 'unknown vulnerability';

                $emoji = match (strtolower($severity)) {
                    'critical', 'high' => '🔴',
                    'medium', 'moderate' => '🟠',
                    default => '🟡',
                };

                $vulnList .= "{$emoji} *[{$severity}]* `{$package}`: {$title}\n";
            }

            if (count($resultsData) > 5) {
                $remaining = count($resultsData) - 5;
                $vulnList .= "... and {$remaining} more vulnerabilities";
            }

            $message->sectionBlock(function (SectionBlock $block) use ($vulnList) {
                $block->text($vulnList)->markdown();
            });
        }

        $message->contextBlock(function ($block) {
            $block->text('Please review and update the affected packages as soon as possible.');
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
                return [
                    'source' => $result->source,
                    'vulnerabilities_count' => $result->vulnerabilities_count,
                    'packages' => $result->results,
                ];
            }, $this->results),
            'url' => $this->dashboardUrl(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function getSlackFields(): array
    {
        $fields = [];

        foreach ($this->results as $result) {
            if ($result->has_issues) {
                $fields[$result->source] = "{$result->vulnerabilities_count} vulnerabilities";
            }
        }

        return $fields;
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

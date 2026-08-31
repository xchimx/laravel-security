<?php

namespace Xchimx\LaravelSecurity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Support\Facades\Route;
use Xchimx\LaravelSecurity\Models\SecurityAudit;

class OutdatedPackagesNotification extends Notification
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
        $totalOutdated = (int) array_sum(array_column($this->results, 'outdated_count'));
        $appName = (string) config('app.name');

        $message = (new MailMessage)
            ->subject(__('security::notifications.outdated.subject', ['app' => $appName]))
            ->greeting(__('security::notifications.outdated.greeting', ['app' => $appName]))
            ->line(__('security::notifications.outdated.summary', ['count' => $totalOutdated]));

        foreach ($this->results as $result) {
            if (! $result->has_issues || ! is_array($result->results)) {
                continue;
            }

            $message->line(__('security::notifications.outdated.source_summary', [
                'source' => $result->source,
                'count' => $result->outdated_count,
            ]));

            $resultsData = $result->results ?? [];
            $packages = array_slice($resultsData, 0, 5);
            foreach ($packages as $pkg) {
                $package = is_string($pkg['package'] ?? null) ? $pkg['package'] : 'unknown';
                $current = is_string($pkg['current'] ?? null) ? $pkg['current'] : 'unknown';
                $latest = is_string($pkg['latest'] ?? null) ? $pkg['latest'] : 'unknown package';
                $message->line("- {$package}: {$current} → {$latest}");
            }

            if (count($resultsData) > 5) {
                $remaining = count($resultsData) - 5;
                $message->line(__('security::notifications.outdated.more', ['count' => $remaining]));
            }
        }

        $message->action(__('security::notifications.outdated.action'), $this->dashboardUrl())
            ->line(__('security::notifications.outdated.footer'));

        return $message;
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $totalOutdated = (int) array_sum(array_column($this->results, 'outdated_count'));
        $appName = (string) config('app.name');
        $appRoute = $this->dashboardUrl();

        $message = (new SlackMessage)
            ->text(__('security::notifications.outdated.slack_text', ['app' => $appName]))
            ->headerBlock(__('security::notifications.outdated.slack_header', ['app' => $appName]))
            ->sectionBlock(function (SectionBlock $block) use ($totalOutdated, $appRoute) {
                $summary = __('security::notifications.outdated.slack_summary', ['count' => $totalOutdated]);
                $dashboardLabel = __('security::notifications.view_dashboard');
                $block->text("{$summary}\n<{$appRoute}|{$dashboardLabel}>")
                    ->markdown();
            });

        foreach ($this->results as $result) {
            if (! $result->has_issues || empty($result->results)) {
                continue;
            }

            $message->dividerBlock();
            $message->sectionBlock(function (SectionBlock $block) use ($result) {
                $block->text(__('security::notifications.outdated.slack_source_summary', [
                    'source' => ucfirst($result->source),
                    'count' => $result->outdated_count,
                ]))->markdown();
            });

            $resultsData = $result->results ?? [];
            $packages = array_slice($resultsData, 0, 5);
            $packageList = '';

            foreach ($packages as $pkg) {
                $name = is_string($pkg['package'] ?? null) ? $pkg['package'] : 'unknown';
                $current = is_string($pkg['current'] ?? null) ? $pkg['current'] : '?';
                $latest = is_string($pkg['latest'] ?? null) ? $pkg['latest'] : '?';

                $packageList .= "• `{$name}`: {$current} → *{$latest}*\n";
            }

            if (count($resultsData) > 5) {
                $remaining = count($resultsData) - 5;
                $packageList .= __('security::notifications.outdated.more', ['count' => $remaining]);
            }

            $message->sectionBlock(function (SectionBlock $block) use ($packageList) {
                $block->text($packageList)->markdown();
            });
        }

        $message->contextBlock(function ($block) {
            $block->text(__('security::notifications.outdated.footer'));
        });

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $totalOutdated = (int) array_sum(array_column($this->results, 'outdated_count'));

        return [
            'type' => 'outdated_packages',
            'app_name' => config('app.name'),
            'total_outdated' => $totalOutdated,
            'results' => array_map(function (SecurityAudit $result): array {
                return [
                    'source' => $result->source,
                    'outdated_count' => $result->outdated_count,
                    'packages' => $result->results,
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

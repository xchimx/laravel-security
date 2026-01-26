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
            ->subject("📦 Outdated Packages Report - {$appName}")
            ->greeting("Package Update Report for {$appName}")
            ->line("We found **{$totalOutdated} outdated packages** in your application dependencies.");

        foreach ($this->results as $result) {
            if (! $result->has_issues || ! is_array($result->results)) {
                continue;
            }

            $message->line("**{$result->source}**: {$result->outdated_count} outdated packages");

            // Add first 5 outdated packages
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
                $message->line("... and {$remaining} more packages");
            }
        }

        $appRoute = $this->dashboardUrl();
        $message->action('View Full Report', $appRoute)
            ->line('Consider updating these packages to their latest versions.');

        return $message;
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $totalOutdated = (int) array_sum(array_column($this->results, 'outdated_count'));
        $appName = (string) config('app.name');
        $appRoute = $this->dashboardUrl();

        $message = (new SlackMessage)
            ->text("📦 Outdated packages detected in {$appName}")
            ->headerBlock("📦 Outdated Packages Report - {$appName}")
            ->sectionBlock(function (SectionBlock $block) use ($totalOutdated, $appRoute) {
                $block->text("We found *{$totalOutdated} outdated packages* in your application dependencies.\n<{$appRoute}|View Full Dashboard>")
                    ->markdown();
            });

        foreach ($this->results as $result) {
            if (! $result->has_issues || empty($result->results)) {
                continue;
            }

            $message->dividerBlock();
            $message->sectionBlock(function (SectionBlock $block) use ($result) {
                $sourceName = ucfirst($result->source);
                $block->text("*{$sourceName}*: {$result->outdated_count} outdated packages")
                    ->markdown();
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
                $packageList .= "... and {$remaining} more packages";
            }

            $message->sectionBlock(function (SectionBlock $block) use ($packageList) {
                $block->text($packageList)->markdown();
            });
        }

        $message->contextBlock(function ($block) {
            $block->text('Consider updating these packages to their latest versions.');
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

    /**
     * @return array<string, string>
     */
    protected function getSlackFields(): array
    {
        $fields = [];

        foreach ($this->results as $result) {
            if ($result->has_issues) {
                $fields[$result->source] = "{$result->outdated_count} outdated packages";
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

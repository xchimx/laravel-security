<?php

namespace Xchimx\LaravelSecurity\Services;

use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Xchimx\LaravelSecurity\Models\SecurityAudit;
use Xchimx\LaravelSecurity\Notifications\OutdatedPackagesNotification;
use Xchimx\LaravelSecurity\Notifications\SecurityAuditNotification;
use Xchimx\LaravelSecurity\Support\AuditDiff;
use Xchimx\LaravelSecurity\Support\Severity;

class SecurityNotifier
{
    /**
     * @var array<int, string>
     */
    private array $allowedChannels = ['mail', 'database', 'slack'];

    /**
     * With only_new enabled the severity threshold applies to the new
     * vulnerabilities only; otherwise a single old high finding would keep
     * triggering notifications for new below-threshold findings.
     *
     * @param  array<int, SecurityAudit>  $results
     */
    public function sendAuditNotification(array $results): bool
    {
        $threshold = Severity::fromString((string) config('security.notifications.min_severity', 'low'));
        $diffs = [];

        if (config('security.notifications.only_new', false)) {
            $hasNotifiableNewVulnerability = false;

            foreach ($results as $result) {
                $diff = AuditDiff::forAudit($result);
                $diffs[$result->source] = $diff;

                if ($this->anyVulnerabilityMeets($diff->new, $threshold)) {
                    $hasNotifiableNewVulnerability = true;
                }
            }

            if (! $hasNotifiableNewVulnerability) {
                return false;
            }
        } elseif (! $this->meetsMinimumSeverity($results, $threshold)) {
            return false;
        }

        return $this->dispatch(
            fn (array $channels): Notification => new SecurityAuditNotification($results, $channels, $diffs)
        );
    }

    /**
     * @param  array<int, SecurityAudit>  $results
     */
    public function sendOutdatedNotification(array $results): bool
    {
        return $this->dispatch(
            fn (array $channels): Notification => new OutdatedPackagesNotification($results, $channels)
        );
    }

    /**
     * @param  callable(array<int, string>): Notification  $makeNotification
     */
    protected function dispatch(callable $makeNotification): bool
    {
        $sent = false;

        $userChannels = $this->resolveUserChannels();

        if ($userChannels !== []) {
            $sent = $this->notifyConfiguredUser($makeNotification($userChannels));
        }

        $anonymousChannels = $this->resolveAnonymousChannels();

        if ($anonymousChannels !== []) {
            $this->notifyConfiguredAnonymous($makeNotification($anonymousChannels), $anonymousChannels);
            $sent = true;
        }

        return $sent;
    }

    /**
     * @return array<int, string>
     */
    private function resolveUserChannels(): array
    {
        $config = config('security.notifications.channels', []);

        $channels = [];

        if (($config['database'] ?? false) === true) {
            $channels[] = 'database';
        }

        if (($config['database_mail'] ?? false) === true) {
            $channels[] = 'mail';
        }

        return $this->filterAllowedChannels($channels);
    }

    /**
     * @return array<int, string>
     */
    private function resolveAnonymousChannels(): array
    {
        $config = config('security.notifications.channels', []);

        $channels = [];

        if (
            ($config['mail'] ?? false) === true
            && config('security.notifications.mail_to') !== null
        ) {
            $channels[] = 'mail';
        }

        if (($config['slack'] ?? false) === true) {
            $channels[] = 'slack';
        }

        return $this->filterAllowedChannels($channels);
    }

    /**
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    private function filterAllowedChannels(array $channels): array
    {
        $channels = array_values(array_unique($channels));

        return array_values(array_filter(
            $channels,
            fn (string $channel): bool => in_array($channel, $this->allowedChannels, true)
        ));
    }

    private function notifyConfiguredUser(Notification $notification): bool
    {
        /** @var string|null $userModel */
        $userModel = config('security.notifications.user_model');
        /** @var mixed $userId */
        $userId = config('security.notifications.user_id');

        if (
            ! is_string($userModel)
            || ! class_exists($userModel)
            || ! is_subclass_of($userModel, Model::class)
            || $userId === null
        ) {
            return false;
        }

        /** @var Model|null $user */
        $user = $userModel::query()->find($userId);

        if ($user === null) {
            return false;
        }

        /** @var Dispatcher $dispatcher */
        $dispatcher = app(Dispatcher::class);
        $dispatcher->send($user, $notification);

        return true;
    }

    /**
     * @param  array<int, string>  $channels
     */
    private function notifyConfiguredAnonymous(Notification $notification, array $channels): void
    {
        $anonymousNotifiable = new AnonymousNotifiable;

        if (in_array('mail', $channels, true)) {
            $anonymousNotifiable->route('mail', config('security.notifications.mail_to'));
        }

        if (in_array('slack', $channels, true)) {
            $anonymousNotifiable->route('slack', config('services.slack.notifications.channel'));
        }

        $anonymousNotifiable->notify($notification);
    }

    /**
     * @param  array<int, SecurityAudit>  $results
     */
    protected function meetsMinimumSeverity(array $results, Severity $threshold): bool
    {
        if ($threshold === Severity::Low || $threshold === Severity::Unknown) {
            return true;
        }

        foreach ($results as $result) {
            if ($result->hasVulnerabilityMeeting($threshold)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $vulnerabilities
     */
    protected function anyVulnerabilityMeets(array $vulnerabilities, Severity $threshold): bool
    {
        foreach ($vulnerabilities as $vulnerability) {
            $severityValue = $vulnerability['severity'] ?? null;
            $severity = Severity::fromString(is_string($severityValue) ? $severityValue : null);

            if ($severity->meetsThreshold($threshold)) {
                return true;
            }
        }

        return false;
    }
}

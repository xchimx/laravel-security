<?php

namespace Xchimx\LaravelSecurity\Commands\Concerns;

use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;

trait SendsSecurityNotifications
{
    /**
     * @var array<int, string>
     */
    private array $allowedChannels = ['mail', 'database', 'database_mail', 'slack'];

    /**
     * @param  callable(array<int, string>): Notification  $makeNotification
     */
    protected function sendConfiguredNotifications(callable $makeNotification): void
    {
        /** @var Dispatcher $dispatcher */
        $dispatcher = app(Dispatcher::class);

        $userChannels = $this->resolveUserChannels();
        if (! empty($userChannels)) {
            $this->notifyConfiguredUser($dispatcher, $makeNotification($userChannels));
        }

        $anonChannels = $this->resolveAnonymousChannels();
        if (! empty($anonChannels)) {
            $this->notifyConfiguredAnonymous($makeNotification($anonChannels), $anonChannels);
        }
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

    private function notifyConfiguredUser(Dispatcher $dispatcher, Notification $notification): void
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
            return;
        }

        /** @var Model|null $user */
        $user = $userModel::query()->find($userId);

        if ($user !== null) {
            $dispatcher->send($user, $notification);
        }
    }

    /**
     * @param  array<int, string>  $channels
     */
    private function notifyConfiguredAnonymous(Notification $notification, array $channels): void
    {
        $anon = new AnonymousNotifiable;

        if (in_array('mail', $channels, true)) {
            $anon->route('mail', config('security.notifications.mail_to'));
        }

        if (in_array('slack', $channels, true)) {
            $anon->route('slack', config('services.slack.notifications.channel'));
        }

        $anon->notify($notification);
    }
}

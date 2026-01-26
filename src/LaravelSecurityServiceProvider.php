<?php

namespace Xchimx\LaravelSecurity;

use Illuminate\Console\Scheduling\Schedule;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Xchimx\LaravelSecurity\Commands\RunOutdatedCheckCommand;
use Xchimx\LaravelSecurity\Commands\RunSecurityAuditCommand;
use Xchimx\LaravelSecurity\Components\SecurityDashboard;

class LaravelSecurityServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-security')
            ->hasRoute('web')
            ->hasConfigFile('security')
            ->hasViews('security')
            ->hasViewComponent('security', SecurityDashboard::class)
            ->hasMigration('create_security_audits_table')
            ->hasCommands([
                RunSecurityAuditCommand::class,
                RunOutdatedCheckCommand::class,
            ]);

    }

    public function bootingPackage(): void
    {
        $this->registerSchedule();
    }

    protected function registerSchedule(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            if (config('security.audit.enabled', true)) {
                $schedule->command('security:audit')
                    ->daily()
                    ->at(config('security.audit.time', '02:00'));
            }

            if (config('security.outdated.enabled', true)) {
                $schedule->command('security:outdated')
                    ->weekly()
                    ->mondays()
                    ->at(config('security.outdated.time', '03:00'));
            }
        });
    }
}

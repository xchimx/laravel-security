<?php

use Illuminate\Console\Command;
use Xchimx\LaravelSecurity\Commands\RunOutdatedCheckCommand;
use Xchimx\LaravelSecurity\Commands\RunSecurityAuditCommand;
use Xchimx\LaravelSecurity\Services\AuditService;
use Xchimx\LaravelSecurity\Services\OutdatedService;

use function Pest\Laravel\artisan;
use function Pest\Laravel\mock;

it('can run commands with disabled config', function () {

    config()->set('security.audit.composer', false);
    config()->set('security.audit.npm', false);
    config()->set('security.outdated.composer', false);
    config()->set('security.outdated.npm', false);

    mock(OutdatedService::class);
    mock(AuditService::class);

    artisan(RunOutdatedCheckCommand::class)->assertExitCode(Command::SUCCESS);
    artisan(RunSecurityAuditCommand::class)->assertExitCode(Command::SUCCESS);
});

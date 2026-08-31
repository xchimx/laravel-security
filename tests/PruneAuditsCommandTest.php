<?php

use Illuminate\Console\Scheduling\Schedule;
use Xchimx\LaravelSecurity\Models\SecurityAudit;

it('prunes audit records older than the retention period', function () {
    config()->set('security.storage.retention_days', 90);

    $oldAudit = SecurityAudit::factory()->create(['executed_at' => now()->subDays(120)]);
    $recentAudit = SecurityAudit::factory()->create(['executed_at' => now()->subDays(30)]);

    $this->artisan('security:prune')
        ->expectsOutputToContain('Pruned 1 security audit records older than 90 days.')
        ->assertSuccessful();

    expect(SecurityAudit::query()->find($oldAudit->id))->toBeNull()
        ->and(SecurityAudit::query()->find($recentAudit->id))->not->toBeNull();
});

it('does not prune anything when retention days is zero', function () {
    config()->set('security.storage.retention_days', 0);

    SecurityAudit::factory()->create(['executed_at' => now()->subDays(500)]);

    $this->artisan('security:prune')
        ->expectsOutputToContain('Pruning is disabled')
        ->assertSuccessful();

    expect(SecurityAudit::query()->count())->toBe(1);
});

it('does not prune anything when retention days is null', function () {
    config()->set('security.storage.retention_days', null);

    SecurityAudit::factory()->create(['executed_at' => now()->subDays(500)]);

    $this->artisan('security:prune')
        ->expectsOutputToContain('Pruning is disabled')
        ->assertSuccessful();

    expect(SecurityAudit::query()->count())->toBe(1);
});

it('registers the prune command in the scheduler', function () {
    $schedule = app(Schedule::class);

    $pruneEvents = collect($schedule->events())
        ->filter(fn ($event) => str_contains((string) $event->command, 'security:prune'));

    expect($pruneEvents)->toHaveCount(1);
});

it('does not schedule pruning when disabled', function () {
    config()->set('security.storage.prune_enabled', false);

    $schedule = app(Schedule::class);

    $pruneEvents = collect($schedule->events())
        ->filter(fn ($event) => str_contains((string) $event->command, 'security:prune'));

    expect($pruneEvents)->toHaveCount(0);
});

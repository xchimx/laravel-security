<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Xchimx\LaravelSecurity\Services\AuditService;

beforeEach(function () {
    $this->workingDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'laravel-security-osv-'.uniqid();
    File::makeDirectory($this->workingDirectory, 0755, true);

    config()->set('security.audit.driver', 'api');
    config()->set('security.audit.working_directory', $this->workingDirectory);
});

afterEach(function () {
    File::deleteDirectory($this->workingDirectory);
});

function writeComposerLock(string $directory, array $packages): void
{
    file_put_contents($directory.DIRECTORY_SEPARATOR.'composer.lock', json_encode([
        'packages' => $packages,
        'packages-dev' => [],
    ]));
}

function writePackageLock(string $directory, array $packages): void
{
    file_put_contents($directory.DIRECTORY_SEPARATOR.'package-lock.json', json_encode([
        'lockfileVersion' => 3,
        'packages' => $packages,
    ]));
}

it('finds composer vulnerabilities via the osv api without the composer binary', function () {
    writeComposerLock($this->workingDirectory, [
        ['name' => 'vendor/package', 'version' => 'v1.0.0'],
    ]);

    Http::fake([
        'api.osv.dev/v1/querybatch' => Http::response([
            'results' => [
                ['vulns' => [['id' => 'GHSA-aaaa-bbbb-cccc', 'modified' => '2026-01-01T00:00:00Z']]],
            ],
        ]),
        'api.osv.dev/v1/vulns/*' => Http::response([
            'id' => 'GHSA-aaaa-bbbb-cccc',
            'summary' => 'Example RCE vulnerability',
            'aliases' => ['CVE-2026-1234'],
            'database_specific' => ['severity' => 'HIGH'],
        ]),
    ]);

    $audit = app(AuditService::class)->runComposerAudit();

    expect($audit->has_issues)->toBeTrue()
        ->and($audit->vulnerabilities_count)->toBe(1)
        ->and($audit->results[0])->toMatchArray([
            'package' => 'vendor/package',
            'title' => 'Example RCE vulnerability',
            'cve' => 'CVE-2026-1234',
            'severity' => 'high',
            'link' => 'https://osv.dev/vulnerability/GHSA-aaaa-bbbb-cccc',
        ]);

    Http::assertSent(function ($request): bool {
        return str_contains($request->url(), 'querybatch')
            && $request['queries'][0]['package']['ecosystem'] === 'Packagist'
            && $request['queries'][0]['version'] === '1.0.0';
    });
});

it('stores a clean audit when the osv api reports no vulnerabilities', function () {
    writeComposerLock($this->workingDirectory, [
        ['name' => 'vendor/package', 'version' => '2.0.0'],
    ]);

    Http::fake([
        'api.osv.dev/v1/querybatch' => Http::response(['results' => [[]]]),
    ]);

    $audit = app(AuditService::class)->runComposerAudit();

    expect($audit->has_issues)->toBeFalse()
        ->and($audit->vulnerabilities_count)->toBe(0);
});

it('skips dev versions entirely when querying the osv api', function () {
    writeComposerLock($this->workingDirectory, [
        ['name' => 'vendor/package', 'version' => 'dev-main'],
    ]);

    Http::fake();

    $audit = app(AuditService::class)->runComposerAudit();

    expect($audit->has_issues)->toBeFalse();
    Http::assertNothingSent();
});

it('finds npm vulnerabilities via the osv api without the npm binary', function () {
    writePackageLock($this->workingDirectory, [
        '' => ['name' => 'root'],
        'node_modules/lodash' => ['version' => '4.17.20'],
    ]);

    Http::fake([
        'api.osv.dev/v1/querybatch' => Http::response([
            'results' => [
                ['vulns' => [['id' => 'GHSA-npm1-npm1-npm1']]],
            ],
        ]),
        'api.osv.dev/v1/vulns/*' => Http::response([
            'id' => 'GHSA-npm1-npm1-npm1',
            'summary' => 'Prototype pollution',
            'aliases' => ['CVE-2026-5678'],
            'database_specific' => ['severity' => 'MODERATE'],
        ]),
    ]);

    $audit = app(AuditService::class)->runNpmAudit();

    expect($audit->source)->toBe('npm')
        ->and($audit->has_issues)->toBeTrue()
        ->and($audit->results[0])->toMatchArray([
            'package' => 'lodash',
            'title' => 'Prototype pollution',
            'severity' => 'moderate',
        ]);

    Http::assertSent(function ($request): bool {
        return str_contains($request->url(), 'querybatch')
            && $request['queries'][0]['package']['ecosystem'] === 'npm'
            && $request['queries'][0]['package']['name'] === 'lodash';
    });
});

it('attributes vulnerabilities to the correct package in batch responses', function () {
    writeComposerLock($this->workingDirectory, [
        ['name' => 'vendor/first', 'version' => '1.0.0'],
        ['name' => 'vendor/second', 'version' => '2.0.0'],
        ['name' => 'vendor/third', 'version' => '3.0.0'],
    ]);

    Http::fake([
        'api.osv.dev/v1/querybatch' => Http::response([
            'results' => [
                [],
                ['vulns' => [['id' => 'GHSA-mid1-mid1-mid1']]],
                [],
            ],
        ]),
        'api.osv.dev/v1/vulns/*' => Http::response([
            'id' => 'GHSA-mid1-mid1-mid1',
            'summary' => 'Middle package issue',
            'aliases' => [],
            'database_specific' => ['severity' => 'LOW'],
        ]),
    ]);

    $audit = app(AuditService::class)->runComposerAudit();

    expect($audit->vulnerabilities_count)->toBe(1)
        ->and($audit->results[0]['package'])->toBe('vendor/second');
});

it('queries every distinct version of nested npm duplicates', function () {
    writePackageLock($this->workingDirectory, [
        '' => ['name' => 'root'],
        'node_modules/minimist' => ['version' => '0.0.8'],
        'node_modules/mkdirp' => ['version' => '0.5.1'],
        'node_modules/mkdirp/node_modules/minimist' => ['version' => '1.2.5'],
        'node_modules/@scope/tool' => ['version' => '2.0.0'],
    ]);

    Http::fake([
        'api.osv.dev/v1/querybatch' => Http::response(['results' => [[], [], [], []]]),
    ]);

    $audit = app(AuditService::class)->runNpmAudit();

    expect($audit->has_issues)->toBeFalse();

    Http::assertSent(function ($request): bool {
        if (! str_contains($request->url(), 'querybatch')) {
            return false;
        }

        $queried = array_map(
            fn (array $query): string => $query['package']['name'].'@'.$query['version'],
            $request['queries']
        );

        return $queried === ['minimist@0.0.8', 'mkdirp@0.5.1', 'minimist@1.2.5', '@scope/tool@2.0.0'];
    });
});

it('queries npm alias installs under their real registry name', function () {
    writePackageLock($this->workingDirectory, [
        '' => ['name' => 'root'],
        'node_modules/my-alias' => ['name' => 'real-package', 'version' => '1.0.0'],
    ]);

    Http::fake([
        'api.osv.dev/v1/querybatch' => Http::response(['results' => [[]]]),
    ]);

    app(AuditService::class)->runNpmAudit();

    Http::assertSent(function ($request): bool {
        return str_contains($request->url(), 'querybatch')
            && $request['queries'][0]['package']['name'] === 'real-package';
    });
});

it('fails a fail-on run when an enabled source is unavailable', function () {
    Notification::fake();

    writeComposerLock($this->workingDirectory, [
        ['name' => 'vendor/package', 'version' => '2.0.0'],
    ]);

    Http::fake([
        'api.osv.dev/v1/querybatch' => Http::response(['results' => [[]]]),
    ]);

    $this->artisan('security:audit', ['--fail-on' => 'high'])
        ->expectsOutputToContain('--fail-on is set but an enabled audit source was not available')
        ->assertFailed();
});

it('warns about a missing package lock without aborting the composer audit', function () {
    Notification::fake();

    writeComposerLock($this->workingDirectory, [
        ['name' => 'vendor/package', 'version' => '2.0.0'],
    ]);

    Http::fake([
        'api.osv.dev/v1/querybatch' => Http::response(['results' => [[]]]),
    ]);

    $this->artisan('security:audit')
        ->expectsOutputToContain('No vulnerabilities found in composer packages')
        ->expectsOutputToContain('Npm is not available')
        ->assertSuccessful();
});

it('reports an osv api failure as a command error instead of crashing', function () {
    Notification::fake();
    config()->set('security.audit.npm', false);

    writeComposerLock($this->workingDirectory, [
        ['name' => 'vendor/package', 'version' => '2.0.0'],
    ]);

    Http::fake([
        'api.osv.dev/*' => Http::response('server error', 500),
    ]);

    $this->artisan('security:audit')
        ->expectsOutputToContain('Composer check failed')
        ->assertFailed();
});

<?php

namespace Xchimx\LaravelSecurity\Services;

use Illuminate\Support\Facades\Process;
use Xchimx\LaravelSecurity\Models\SecurityAudit;

class OutdatedService
{
    /**
     * Run composer outdated check
     */
    public function runComposerOutdated(): SecurityAudit
    {
        $result = Process::run('composer outdated --format=json --direct');

        $output = $result->output();
        $data = json_decode($output, true);

        /** @var array<int, array<string, mixed>> $outdated */
        $outdated = [];
        $count = 0;

        if (is_array($data) && isset($data['installed']) && is_array($data['installed'])) {
            foreach ($data['installed'] as $package) {
                if (! is_array($package)) {
                    continue;
                }

                $version = isset($package['version']) && is_string($package['version'])
                    ? $package['version']
                    : null;
                $latest = isset($package['latest']) && is_string($package['latest'])
                    ? $package['latest']
                    : null;

                if ($latest !== null && $version !== $latest) {
                    $outdated[] = [
                        'package' => isset($package['name']) && is_string($package['name'])
                            ? $package['name']
                            : 'unknown',
                        'current' => $version,
                        'latest' => $latest,
                        'description' => isset($package['description']) && is_string($package['description'])
                            ? $package['description']
                            : null,
                    ];
                    $count++;
                }
            }
        }

        return SecurityAudit::create([
            'type' => 'outdated',
            'source' => 'composer',
            'results' => $outdated,
            'outdated_count' => $count,
            'has_issues' => $count > 0,
            'raw_output' => $output,
            'executed_at' => now(),
        ]);
    }

    /**
     * Run npm outdated check
     */
    public function runNpmOutdated(): SecurityAudit
    {
        $result = Process::run('npm outdated --json');

        $output = $result->output();
        $data = json_decode($output, true);

        /** @var array<int, array<string, mixed>> $outdated */
        $outdated = [];
        $count = 0;

        if (is_array($data)) {
            foreach ($data as $package => $info) {
                if (! is_array($info)) {
                    continue;
                }

                $outdated[] = [
                    'package' => is_string($package) ? $package : 'unknown',
                    'current' => isset($info['current']) && is_string($info['current'])
                        ? $info['current']
                        : null,
                    'wanted' => isset($info['wanted']) && is_string($info['wanted'])
                        ? $info['wanted']
                        : null,
                    'latest' => isset($info['latest']) && is_string($info['latest'])
                        ? $info['latest']
                        : null,
                    'location' => isset($info['location']) && is_string($info['location'])
                        ? $info['location']
                        : null,
                ];
                $count++;
            }
        }

        return SecurityAudit::create([
            'type' => 'outdated',
            'source' => 'npm',
            'results' => $outdated,
            'outdated_count' => $count,
            'has_issues' => $count > 0,
            'raw_output' => $output,
            'executed_at' => now(),
        ]);
    }

    /**
     * Check if composer is available
     */
    public function isComposerAvailable(): bool
    {
        $result = Process::run('composer --version');

        return $result->successful();
    }

    /**
     * Check if npm is available
     */
    public function isNpmAvailable(): bool
    {
        $result = Process::run('npm --version');

        return $result->successful();
    }
}

<?php

namespace Xchimx\LaravelSecurity\Services;

use Illuminate\Support\Facades\Process;
use Xchimx\LaravelSecurity\Models\SecurityAudit;

class AuditService
{
    public function __construct(
        protected LockfileParser $lockfileParser,
        protected OsvClient $osvClient,
    ) {}

    /**
     * Run composer audit
     */
    public function runComposerAudit(): SecurityAudit
    {
        if ($this->usesApiDriver()) {
            return $this->runComposerAuditViaApi();
        }

        $result = Process::run('composer audit --format=json');

        $output = $result->output();
        $data = json_decode($output, true);

        /** @var array<int, array<string, mixed>> $vulnerabilities */
        $vulnerabilities = [];
        $count = 0;

        if (is_array($data) && isset($data['advisories']) && is_array($data['advisories'])) {
            foreach ($data['advisories'] as $package => $advisoryList) {
                if (! is_array($advisoryList)) {
                    continue;
                }

                foreach ($advisoryList as $advisory) {
                    if (! is_array($advisory)) {
                        continue;
                    }

                    $vulnerabilities[] = [
                        'package' => is_string($package) ? $package : 'unknown',
                        'title' => isset($advisory['title']) && is_string($advisory['title'])
                            ? $advisory['title']
                            : 'Unknown vulnerability',
                        'cve' => isset($advisory['cve']) && is_string($advisory['cve'])
                            ? $advisory['cve']
                            : null,
                        'severity' => isset($advisory['severity']) && is_string($advisory['severity'])
                            ? $advisory['severity']
                            : 'unknown',
                        'link' => isset($advisory['link']) && is_string($advisory['link'])
                            ? $advisory['link']
                            : null,
                    ];
                    $count++;
                }
            }
        }

        return SecurityAudit::create([
            'type' => 'audit',
            'source' => 'composer',
            'results' => $vulnerabilities,
            'vulnerabilities_count' => $count,
            'has_issues' => $count > 0,
            'raw_output' => $output,
            'executed_at' => now(),
        ]);
    }

    /**
     * Run npm audit
     */
    public function runNpmAudit(): SecurityAudit
    {
        if ($this->usesApiDriver()) {
            return $this->runNpmAuditViaApi();
        }

        $result = Process::run('npm audit --json');

        $output = $result->output();
        $data = json_decode($output, true);

        /** @var array<int, array<string, mixed>> $vulnerabilities */
        $vulnerabilities = [];
        $count = 0;

        if (is_array($data) && isset($data['vulnerabilities']) && is_array($data['vulnerabilities'])) {
            foreach ($data['vulnerabilities'] as $package => $vuln) {
                if (! is_array($vuln)) {
                    continue;
                }

                if (isset($vuln['via']) && is_array($vuln['via'])) {
                    foreach ($vuln['via'] as $advisory) {
                        if (is_array($advisory)) {
                            $vulnerabilities[] = [
                                'package' => is_string($package) ? $package : 'unknown',
                                'title' => isset($advisory['title']) && is_string($advisory['title'])
                                    ? $advisory['title']
                                    : 'Unknown vulnerability',
                                'severity' => isset($advisory['severity']) && is_string($advisory['severity'])
                                    ? $advisory['severity']
                                    : 'unknown',
                                'range' => isset($advisory['range']) && is_string($advisory['range'])
                                    ? $advisory['range']
                                    : null,
                                'url' => isset($advisory['url']) && is_string($advisory['url'])
                                    ? $advisory['url']
                                    : null,
                            ];
                            $count++;
                        }
                    }
                }
            }
        }

        return SecurityAudit::create([
            'type' => 'audit',
            'source' => 'npm',
            'results' => $vulnerabilities,
            'vulnerabilities_count' => $count,
            'has_issues' => $count > 0,
            'raw_output' => $output,
            'executed_at' => now(),
        ]);
    }

    /**
     * Check if the composer audit source is available. With the API driver
     * this only requires a composer.lock file instead of the composer binary.
     */
    public function isComposerAvailable(): bool
    {
        if ($this->usesApiDriver()) {
            return is_file($this->workingDirectory().DIRECTORY_SEPARATOR.'composer.lock');
        }

        $result = Process::run('composer --version');

        return $result->successful();
    }

    /**
     * Check if the npm audit source is available. With the API driver
     * this only requires a package-lock.json file instead of the npm binary.
     */
    public function isNpmAvailable(): bool
    {
        if ($this->usesApiDriver()) {
            return is_file($this->workingDirectory().DIRECTORY_SEPARATOR.'package-lock.json');
        }

        $result = Process::run('npm --version');

        return $result->successful();
    }

    protected function runComposerAuditViaApi(): SecurityAudit
    {
        $packages = $this->lockfileParser->composerPackages($this->workingDirectory());
        $vulnerabilities = $this->osvClient->findVulnerabilities($packages, 'Packagist');

        return SecurityAudit::create([
            'type' => 'audit',
            'source' => 'composer',
            'results' => $vulnerabilities,
            'vulnerabilities_count' => count($vulnerabilities),
            'has_issues' => count($vulnerabilities) > 0,
            'raw_output' => (string) json_encode($vulnerabilities),
            'executed_at' => now(),
        ]);
    }

    protected function runNpmAuditViaApi(): SecurityAudit
    {
        $packages = $this->lockfileParser->npmPackages($this->workingDirectory());
        $vulnerabilities = $this->osvClient->findVulnerabilities($packages, 'npm');

        return SecurityAudit::create([
            'type' => 'audit',
            'source' => 'npm',
            'results' => $vulnerabilities,
            'vulnerabilities_count' => count($vulnerabilities),
            'has_issues' => count($vulnerabilities) > 0,
            'raw_output' => (string) json_encode($vulnerabilities),
            'executed_at' => now(),
        ]);
    }

    protected function usesApiDriver(): bool
    {
        return config('security.audit.driver', 'cli') === 'api';
    }

    protected function workingDirectory(): string
    {
        $configured = config('security.audit.working_directory');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return base_path();
    }
}

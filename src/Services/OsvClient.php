<?php

namespace Xchimx\LaravelSecurity\Services;

use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OsvClient
{
    protected const BASE_URL = 'https://api.osv.dev/v1';

    protected const BATCH_SIZE = 500;

    /**
     * Query the OSV.dev database for known vulnerabilities affecting the
     * given locked package versions. Version matching happens server-side.
     *
     * @param  array<int, array{name: string, version: string}>  $packages
     * @param  string  $ecosystem  "Packagist" or "npm"
     * @return array<int, array<string, mixed>>
     */
    public function findVulnerabilities(array $packages, string $ecosystem): array
    {
        $queries = [];
        $queryPackageNames = [];

        foreach ($packages as $package) {
            $normalizedVersion = $this->normalizeVersion($package['version']);

            if ($normalizedVersion === null) {
                Log::info("laravel-security: skipping [{$package['name']}] ({$package['version']}) - development versions cannot be matched against OSV.");

                continue;
            }

            $queries[] = [
                'package' => [
                    'name' => $package['name'],
                    'ecosystem' => $ecosystem,
                ],
                'version' => $normalizedVersion,
            ];
            $queryPackageNames[] = $package['name'];
        }

        $affected = [];

        foreach (array_chunk($queries, self::BATCH_SIZE) as $chunkIndex => $chunk) {
            try {
                $response = Http::timeout(30)
                    ->retry(2, 500)
                    ->post(self::BASE_URL.'/querybatch', ['queries' => $chunk])
                    ->throw()
                    ->json();
            } catch (HttpClientException $exception) {
                throw new RuntimeException("OSV.dev batch lookup failed: {$exception->getMessage()}", previous: $exception);
            }

            $results = is_array($response) && is_array($response['results'] ?? null) ? $response['results'] : [];

            foreach ($results as $resultIndex => $result) {
                if (! is_array($result) || ! is_array($result['vulns'] ?? null)) {
                    continue;
                }

                $packageName = $queryPackageNames[$chunkIndex * self::BATCH_SIZE + $resultIndex] ?? 'unknown';

                foreach ($result['vulns'] as $vulnerability) {
                    $vulnerabilityId = is_array($vulnerability) ? ($vulnerability['id'] ?? null) : null;

                    if (is_string($vulnerabilityId) && $vulnerabilityId !== '') {
                        $affected[] = ['package' => $packageName, 'id' => $vulnerabilityId];
                    }
                }
            }
        }

        return $this->hydrateDetails($affected);
    }

    /**
     * @param  array<int, array{package: string, id: string}>  $affected
     * @return array<int, array<string, mixed>>
     */
    protected function hydrateDetails(array $affected): array
    {
        $details = [];

        foreach (array_unique(array_column($affected, 'id')) as $vulnerabilityId) {
            try {
                $details[$vulnerabilityId] = Http::timeout(30)
                    ->retry(2, 500)
                    ->get(self::BASE_URL.'/vulns/'.rawurlencode($vulnerabilityId))
                    ->throw()
                    ->json();
            } catch (HttpClientException $exception) {
                throw new RuntimeException("OSV.dev detail lookup for [{$vulnerabilityId}] failed: {$exception->getMessage()}", previous: $exception);
            }
        }

        $vulnerabilities = [];

        foreach ($affected as $entry) {
            $detail = $details[$entry['id']] ?? [];
            $detail = is_array($detail) ? $detail : [];

            $vulnerabilities[] = [
                'package' => $entry['package'],
                'title' => $this->titleFrom($detail, $entry['id']),
                'cve' => $this->cveFrom($detail),
                'severity' => $this->severityFrom($detail),
                'link' => 'https://osv.dev/vulnerability/'.$entry['id'],
            ];
        }

        return $vulnerabilities;
    }

    protected function normalizeVersion(string $version): ?string
    {
        $version = ltrim(trim($version), 'vV');

        if ($version === '' || str_starts_with($version, 'dev-') || str_ends_with($version, '-dev')) {
            return null;
        }

        return $version;
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    protected function titleFrom(array $detail, string $fallbackId): string
    {
        $summary = $detail['summary'] ?? null;

        if (is_string($summary) && $summary !== '') {
            return $summary;
        }

        return $fallbackId;
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    protected function cveFrom(array $detail): ?string
    {
        $aliases = is_array($detail['aliases'] ?? null) ? $detail['aliases'] : [];

        foreach ($aliases as $alias) {
            if (is_string($alias) && str_starts_with($alias, 'CVE-')) {
                return $alias;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $detail
     */
    protected function severityFrom(array $detail): string
    {
        $databaseSpecific = $detail['database_specific'] ?? null;
        $severity = is_array($databaseSpecific) ? ($databaseSpecific['severity'] ?? null) : null;

        if (is_string($severity) && $severity !== '') {
            return strtolower($severity);
        }

        return 'unknown';
    }
}

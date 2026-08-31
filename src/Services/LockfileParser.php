<?php

namespace Xchimx\LaravelSecurity\Services;

use RuntimeException;

class LockfileParser
{
    /**
     * @return array<int, array{name: string, version: string}>
     */
    public function composerPackages(string $directory): array
    {
        $lockfilePath = rtrim($directory, '/\\').DIRECTORY_SEPARATOR.'composer.lock';

        if (! is_file($lockfilePath)) {
            throw new RuntimeException("composer.lock not found in [{$directory}].");
        }

        $data = json_decode((string) file_get_contents($lockfilePath), true);

        if (! is_array($data)) {
            throw new RuntimeException("composer.lock in [{$directory}] contains invalid JSON.");
        }

        $packages = [];

        foreach (['packages', 'packages-dev'] as $section) {
            if (! is_array($data[$section] ?? null)) {
                continue;
            }

            foreach ($data[$section] as $package) {
                if (! is_array($package)) {
                    continue;
                }

                $name = $package['name'] ?? null;
                $version = $package['version'] ?? null;

                if (is_string($name) && $name !== '' && is_string($version) && $version !== '') {
                    $packages[] = ['name' => $name, 'version' => $version];
                }
            }
        }

        return $this->deduplicate($packages);
    }

    /**
     * Supports package-lock.json version 2 and 3 (the "packages" map).
     * The same package can appear in multiple nested node_modules paths
     * with different versions; every distinct name/version pair is returned.
     *
     * @return array<int, array{name: string, version: string}>
     */
    public function npmPackages(string $directory): array
    {
        $lockfilePath = rtrim($directory, '/\\').DIRECTORY_SEPARATOR.'package-lock.json';

        if (! is_file($lockfilePath)) {
            throw new RuntimeException("package-lock.json not found in [{$directory}].");
        }

        $data = json_decode((string) file_get_contents($lockfilePath), true);

        if (! is_array($data)) {
            throw new RuntimeException("package-lock.json in [{$directory}] contains invalid JSON.");
        }

        if (! is_array($data['packages'] ?? null)) {
            throw new RuntimeException('package-lock.json uses the legacy v1 format. Regenerate it with npm 7+ to use the API driver.');
        }

        $packages = [];

        foreach ($data['packages'] as $path => $package) {
            if (! is_string($path) || $path === '' || ! is_array($package)) {
                continue;
            }

            $separatorPosition = strrpos($path, 'node_modules/');

            if ($separatorPosition === false) {
                continue;
            }

            $name = substr($path, $separatorPosition + strlen('node_modules/'));

            // Alias installs register under the alias path but carry the real
            // registry name in the "name" field, which OSV needs for matching.
            $registryName = $package['name'] ?? null;

            if (is_string($registryName) && $registryName !== '') {
                $name = $registryName;
            }

            $version = $package['version'] ?? null;

            if ($name !== '' && is_string($version) && $version !== '') {
                $packages[] = ['name' => $name, 'version' => $version];
            }
        }

        return $this->deduplicate($packages);
    }

    /**
     * @param  array<int, array{name: string, version: string}>  $packages
     * @return array<int, array{name: string, version: string}>
     */
    protected function deduplicate(array $packages): array
    {
        $seen = [];
        $unique = [];

        foreach ($packages as $package) {
            $key = $package['name'].'|'.$package['version'];

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $package;
        }

        return $unique;
    }
}

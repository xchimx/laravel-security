<?php

namespace Xchimx\LaravelSecurity\Support;

use Xchimx\LaravelSecurity\Models\SecurityAudit;

class AuditDiff
{
    /**
     * @param  array<int, array<string, mixed>>  $new
     * @param  array<int, array<string, mixed>>  $known
     */
    protected function __construct(
        public readonly array $new,
        public readonly array $known,
    ) {}

    public static function forAudit(SecurityAudit $audit): self
    {
        $previous = SecurityAudit::query()
            ->where('type', 'audit')
            ->where('source', $audit->source)
            ->where('id', '<', $audit->id)
            ->orderByDesc('id')
            ->first();

        return self::against($audit, $previous);
    }

    public static function against(SecurityAudit $current, ?SecurityAudit $previous): self
    {
        $previousKeys = [];

        foreach ($previous->results ?? [] as $vulnerability) {
            $previousKeys[self::keyFor($vulnerability)] = true;
        }

        $newVulnerabilities = [];
        $knownVulnerabilities = [];

        foreach ($current->results ?? [] as $vulnerability) {
            if (isset($previousKeys[self::keyFor($vulnerability)])) {
                $knownVulnerabilities[] = $vulnerability;
            } else {
                $newVulnerabilities[] = $vulnerability;
            }
        }

        return new self($newVulnerabilities, $knownVulnerabilities);
    }

    public function hasNew(): bool
    {
        return $this->new !== [];
    }

    /**
     * @param  array<string, mixed>  $vulnerability
     */
    protected static function keyFor(array $vulnerability): string
    {
        $cve = $vulnerability['cve'] ?? null;

        if (is_string($cve) && $cve !== '') {
            return 'cve:'.$cve;
        }

        $package = is_string($vulnerability['package'] ?? null) ? $vulnerability['package'] : 'unknown';
        $title = is_string($vulnerability['title'] ?? null) ? $vulnerability['title'] : '';

        return 'pkg:'.$package.'|'.$title;
    }
}

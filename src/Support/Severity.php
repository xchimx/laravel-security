<?php

namespace Xchimx\LaravelSecurity\Support;

enum Severity: int
{
    case Unknown = 0;
    case Low = 1;
    case Medium = 2;
    case High = 3;
    case Critical = 4;

    public static function fromString(?string $value): self
    {
        return match (strtolower(trim((string) $value))) {
            'info', 'none', 'low' => self::Low,
            'medium', 'moderate' => self::Medium,
            'high' => self::High,
            'critical' => self::Critical,
            default => self::Unknown,
        };
    }

    public function isAtLeast(self $threshold): bool
    {
        return $this->value >= $threshold->value;
    }

    /**
     * Unknown severities always meet the threshold so that a missing
     * severity rating never suppresses an alert or a CI failure.
     */
    public function meetsThreshold(self $threshold): bool
    {
        return $this === self::Unknown || $this->isAtLeast($threshold);
    }
}

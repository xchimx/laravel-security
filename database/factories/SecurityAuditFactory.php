<?php

namespace Xchimx\LaravelSecurity\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Xchimx\LaravelSecurity\Models\SecurityAudit;

/**
 * @extends Factory<SecurityAudit>
 */
class SecurityAuditFactory extends Factory
{
    protected $model = SecurityAudit::class;

    /**
     * @return array<model-property<SecurityAudit>, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'audit',
            'source' => 'composer',
            'results' => [],
            'vulnerabilities_count' => 0,
            'outdated_count' => 0,
            'has_issues' => false,
            'raw_output' => null,
            'executed_at' => now(),
        ];
    }
}

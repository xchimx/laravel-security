<?php

namespace Xchimx\LaravelSecurity\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $type
 * @property string $source
 * @property array<int, array<string, mixed>>|null $results
 * @property int $vulnerabilities_count
 * @property int $outdated_count
 * @property bool $has_issues
 * @property string|null $raw_output
 * @property \Illuminate\Support\Carbon $executed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class SecurityAudit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'source',
        'results',
        'vulnerabilities_count',
        'outdated_count',
        'has_issues',
        'raw_output',
        'executed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'results' => 'array',
        'has_issues' => 'boolean',
        'executed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for audit records
     *
     * @param  Builder<SecurityAudit>  $query
     * @return Builder<SecurityAudit>
     */
    public function scopeAudits(Builder $query): Builder
    {
        return $query->where('type', 'audit');
    }

    /**
     * Scope for outdated records
     *
     * @param  Builder<SecurityAudit>  $query
     * @return Builder<SecurityAudit>
     */
    public function scopeOutdated(Builder $query): Builder
    {
        return $query->where('type', 'outdated');
    }

    /**
     * Scope for composer records
     *
     * @param  Builder<SecurityAudit>  $query
     * @return Builder<SecurityAudit>
     */
    public function scopeComposer(Builder $query): Builder
    {
        return $query->where('source', 'composer');
    }

    /**
     * Scope for npm records
     *
     * @param  Builder<SecurityAudit>  $query
     * @return Builder<SecurityAudit>
     */
    public function scopeNpm(Builder $query): Builder
    {
        return $query->where('source', 'npm');
    }

    /**
     * Scope for records with issues
     *
     * @param  Builder<SecurityAudit>  $query
     * @return Builder<SecurityAudit>
     */
    public function scopeWithIssues(Builder $query): Builder
    {
        return $query->where('has_issues', true);
    }

    /**
     * Scope for latest records
     *
     * @param  Builder<SecurityAudit>  $query
     * @return Builder<SecurityAudit>
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('executed_at', 'desc');
    }

    /**
     * Get the latest audit for a specific source
     */
    public static function getLatestAudit(string $source): ?self
    {
        return static::audits()
            ->where('source', $source)
            ->latest()
            ->first();
    }

    /**
     * Get the latest outdated check for a specific source
     */
    public static function getLatestOutdated(string $source): ?self
    {
        return static::outdated()
            ->where('source', $source)
            ->latest()
            ->first();
    }
}

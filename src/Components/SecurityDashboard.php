<?php

namespace Xchimx\LaravelSecurity\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Xchimx\LaravelSecurity\Models\SecurityAudit;

class SecurityDashboard extends Component
{
    public ?SecurityAudit $latestComposerAudit;

    public ?SecurityAudit $latestNpmAudit;

    public ?SecurityAudit $latestComposerOutdated;

    public ?SecurityAudit $latestNpmOutdated;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->latestComposerAudit = SecurityAudit::getLatestAudit('composer');
        $this->latestNpmAudit = SecurityAudit::getLatestAudit('npm');
        $this->latestComposerOutdated = SecurityAudit::getLatestOutdated('composer');
        $this->latestNpmOutdated = SecurityAudit::getLatestOutdated('npm');
    }

    /**
     * Get recent audits with issues
     *
     * @return Collection<int, SecurityAudit>
     */
    public function recentAudits(): Collection
    {
        return SecurityAudit::withIssues()
            ->latest()
            ->take(10)
            ->get();
    }

    public function render(): View
    {
        /** @var view-string $view */
        $view = 'security::components.security-dashboard';

        return view($view, [
            'latestComposerAudit' => $this->latestComposerAudit,
            'latestNpmAudit' => $this->latestNpmAudit,
            'latestComposerOutdated' => $this->latestComposerOutdated,
            'latestNpmOutdated' => $this->latestNpmOutdated,
            'recentAudits' => $this->recentAudits(),
        ]);
    }
}

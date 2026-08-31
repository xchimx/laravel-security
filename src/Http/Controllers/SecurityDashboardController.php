<?php

namespace Xchimx\LaravelSecurity\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;

class SecurityDashboardController extends Controller
{
    public function runAudit(): RedirectResponse
    {
        Artisan::call('security:audit');

        return back()->with('status', __('security::dashboard.flash_audit_done'));
    }

    public function checkOutdated(): RedirectResponse
    {
        Artisan::call('security:outdated');

        return back()->with('status', __('security::dashboard.flash_outdated_done'));
    }
}

<?php

use Illuminate\Support\Facades\Route;
use Xchimx\LaravelSecurity\Http\Controllers\SecurityDashboardController;

Route::group(['middleware' => ['web']], function () {
    Route::post('/security/run-audit', [SecurityDashboardController::class, 'runAudit'])->name('security.run-audit');
    Route::post('/security/run-outdated', [SecurityDashboardController::class, 'checkOutdated'])->name('security.run-outdated');
});

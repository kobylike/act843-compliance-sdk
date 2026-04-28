<?php

use GhanaCompliance\Act843SDK\Http\Controllers\ComplianceReportController;
use GhanaCompliance\Act843SDK\Livewire\SecurityDashboard;
use Illuminate\Support\Facades\Route;
use GhanaCompliance\Act843SDK\Livewire\IpProfile;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/security-dashboard', SecurityDashboard::class)->name('compliance.dashboard');
    Route::get('/ip/{ip}', IpProfile::class)->name('ip.profile');
    Route::get('/compliance-report', [ComplianceReportController::class, 'generate'])->name('compliance.report');
});

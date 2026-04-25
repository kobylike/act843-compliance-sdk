<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/security-dashboard', \GhanaCompliance\Act843SDK\Livewire\SecurityDashboard::class)->name('compliance.dashboard');
    Route::get('/ip/{ip}', \GhanaCompliance\Act843SDK\Livewire\IpProfile::class)->name('compliance.ip.profile');
});

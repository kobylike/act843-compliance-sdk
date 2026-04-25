<?php

use App\Http\Controllers\Api\ComplianceApiController;
use Illuminate\Support\Facades\Route;

Route::post('/compliance/track', [ComplianceApiController::class, 'track']);
Route::post('/compliance/analyze', [ComplianceApiController::class, 'analyze']);

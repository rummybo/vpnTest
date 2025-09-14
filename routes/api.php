<?php

use App\Services\ThemeService;
use Illuminate\Http\Request;

use App\Http\Controllers\Api\ShareController;

Route::post('/share/create', [ShareController::class, 'create']);
Route::get('/share/stats', [ShareController::class, 'stats']);


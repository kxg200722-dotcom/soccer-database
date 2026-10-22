<?php

use App\Http\Controllers\Public\Live\LiveEventController;
use App\Http\Controllers\Public\Live\LiveMatchController;
use Illuminate\Support\Facades\Route;

Route::get('/matches', [LiveMatchController::class, 'index']);
Route::get('/matches/{id}', [LiveMatchController::class, 'show'])->whereNumber('id');
Route::get('/matches/{id}/events', [LiveEventController::class, 'index'])->whereNumber('id');

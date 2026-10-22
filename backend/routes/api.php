<?php

use App\Http\Controllers\Auth\AuthController;
use App\Support\Result;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => Result::ok(['status' => 'up', 'time' => now()->toIso8601String()])->toResponse());

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});

// 公众端（免登）
Route::prefix('public/live')->group(base_path('routes/public_live.php'));

// 管理端（登录 + live:* 权限，权限点在控制器/路由声明）
Route::middleware(['auth:sanctum'])->prefix('admin/live')->group(base_path('routes/admin_live.php'));

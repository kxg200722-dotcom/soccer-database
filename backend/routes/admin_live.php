<?php

use App\Http\Controllers\Admin\Live\FaceVerifyController;
use App\Http\Controllers\Admin\Live\LiveLinkController;
use App\Http\Controllers\Admin\Live\MatchController;
use App\Http\Controllers\Admin\Live\MatchEventController;
use App\Http\Controllers\Admin\Live\MatchFileController;
use App\Http\Controllers\Admin\Live\RefereeController;
use Illuminate\Support\Facades\Route;

// 比赛 CRUD + 状态流转 + 阵容（A1-A7）
Route::get('/matches', [MatchController::class, 'index'])->middleware('permission:live:match:view');
Route::post('/matches', [MatchController::class, 'store'])->middleware('permission:live:match:create');
Route::get('/matches/{match}', [MatchController::class, 'show'])->middleware('permission:live:match:view')->whereNumber('match');
Route::put('/matches/{match}', [MatchController::class, 'update'])->middleware('permission:live:match:update')->whereNumber('match');
Route::delete('/matches/{match}', [MatchController::class, 'destroy'])->middleware('permission:live:match:delete')->whereNumber('match');
Route::patch('/matches/{match}/status', [MatchController::class, 'changeStatus'])->middleware('permission:live:match:update')->whereNumber('match');
Route::put('/matches/{match}/lineup', [MatchController::class, 'updateLineup'])->middleware('permission:live:match:update')->whereNumber('match');

// 事件 CRUD（A10-A13）：goal 联动比分，client_token 幂等
Route::get('/matches/{match}/events', [MatchEventController::class, 'index'])->middleware('permission:live:event:manage')->whereNumber('match');
Route::post('/matches/{match}/events', [MatchEventController::class, 'store'])->middleware('permission:live:event:manage')->whereNumber('match');
Route::put('/events/{event}', [MatchEventController::class, 'update'])->middleware('permission:live:event:manage')->whereNumber('event');
Route::delete('/events/{event}', [MatchEventController::class, 'destroy'])->middleware('permission:live:event:manage')->whereNumber('event');

// 裁判配置（A8-A9）：整体替换，chief/fourth≤1、assistant≤2
Route::get('/matches/{match}/referees', [RefereeController::class, 'index'])->middleware('permission:live:referee:manage')->whereNumber('match');
Route::put('/matches/{match}/referees', [RefereeController::class, 'replace'])->middleware('permission:live:referee:manage')->whereNumber('match');

// 赛事文件（A14-A17）：pdf/jpg/png ≤10MB，私有 disk 鉴权下载
Route::get('/matches/{match}/files', [MatchFileController::class, 'index'])->middleware('permission:live:file:manage')->whereNumber('match');
Route::post('/matches/{match}/files', [MatchFileController::class, 'store'])->middleware('permission:live:file:manage')->whereNumber('match');
Route::get('/files/{file}/download', [MatchFileController::class, 'download'])->middleware('permission:live:file:manage')->whereNumber('file');
Route::delete('/files/{file}', [MatchFileController::class, 'destroy'])->middleware('permission:live:file:manage')->whereNumber('file');

// 直播链接（A18）：url 校验，可置空
Route::put('/matches/{match}/live-link', [LiveLinkController::class, 'update'])->middleware('permission:live:link:manage')->whereNumber('match');

// 人脸核验（A19-A20）：mock driver，超时→50008
Route::post('/matches/{match}/face-verify', [FaceVerifyController::class, 'verify'])->middleware('permission:live:face:manage')->whereNumber('match');
Route::get('/matches/{match}/face-verify-records', [FaceVerifyController::class, 'records'])->middleware('permission:live:face:manage')->whereNumber('match');

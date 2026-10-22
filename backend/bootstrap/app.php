<?php

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use App\Http\Middleware\ApiLogMiddleware;
use App\Http\Middleware\TraceIdMiddleware;
use App\Support\Result;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // trace_id 全局注入，保证 404 等未命中路由的响应也带 trace_id
        $middleware->prepend(TraceIdMiddleware::class);
        $middleware->api(prepend: [
            ApiLogMiddleware::class,
        ]);
        // API 未登录不跳转登录页，直接抛 AuthenticationException → 20001
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/login');
        $middleware->alias([
            'permission' => \App\Http\Middleware\EnsurePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first() ?: ResponseCode::PARAM_INVALID->message();

            return Result::errorCode(ResponseCode::PARAM_INVALID->value, $first, ['errors' => $e->errors()])->toResponse();
        });

        $exceptions->render(function (AuthenticationException $e) {
            return Result::error(ResponseCode::UNAUTHENTICATED)->toResponse();
        });

        $exceptions->render(function (AccessDeniedHttpException $e) {
            return Result::error(ResponseCode::FORBIDDEN)->toResponse();
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return Result::error(ResponseCode::NOT_FOUND)->toResponse();
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Result::error(ResponseCode::NOT_FOUND)->toResponse();
        });

        $exceptions->render(function (BusinessException $e) {
            return Result::errorCode($e->getCode(), $e->getMessage(), $e->data)->toResponse();
        });

        $exceptions->render(function (QueryException $e, Request $request) {
            Log::channel('exception')->error('db error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            return Result::error(ResponseCode::DB_ERROR)->toResponse();
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            Log::channel('exception')->error('system error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Result::error(ResponseCode::SYSTEM_ERROR)->toResponse();
        });
    })->create();

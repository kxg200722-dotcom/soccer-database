<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API 访问日志：url/method/ip/参数/状态码/耗时
 */
class ApiLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        $response = $next($request);

        Log::channel('api')->info('api access', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'params' => $this->safeParams($request),
            'status' => $response->getStatusCode(),
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ]);

        return $response;
    }

    private function safeParams(Request $request): array
    {
        $params = $request->except(['password', 'password_confirmation', 'photo', 'file']);
        $json = json_encode($params, JSON_UNESCAPED_UNICODE);

        return mb_strlen((string) $json) > 2000 ? ['__truncated' => true] : $params;
    }
}

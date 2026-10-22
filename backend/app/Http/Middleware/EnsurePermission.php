<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Exceptions\BusinessException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 简单权限：管理员放行全部 live:* 权限点。
 * 用法：->middleware('permission:live:match:create')，多个权限点逗号分隔，任一满足即放行。
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new BusinessException(ResponseCode::UNAUTHENTICATED);
        }

        // 管理员默认拥有全部 live:* 权限
        if ($user->is_admin) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if (in_array($permission, $user->permissions ?? [], true)) {
                return $next($request);
            }
        }

        throw new BusinessException(ResponseCode::FORBIDDEN);
    }
}

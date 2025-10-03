<?php

declare(strict_types=1);

namespace DFiks\UnPerm\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use BadMethodCallException;
use InvalidArgumentException;

/**
 * Middleware для проверки доступа к ресурсу.
 *
 * Использование в роутах:
 * Route::get('/folders/{folder}', ...)->middleware('unperm:folders,view');
 */
class CheckResourcePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Request     $request
     * @param  Closure     $next
     * @param  string      $resourceKey Ключ ресурса (folders, posts, etc.)
     * @param  string      $action      Действие (view, edit, delete, etc.)
     * @param  string|null $paramName   Имя параметра в роуте (по умолчанию = $resourceKey без 's')
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $resourceKey, string $action, ?string $paramName = null): Response
    {
        $user = $request->user();

        if (!$user) {
            throw new AccessDeniedHttpException('Unauthenticated.');
        }

        if (!method_exists($user, 'hasAction')) {
            throw new BadMethodCallException('User model must use HasPermissions trait');
        }

        // Определяем имя параметра
        $paramName = $paramName ?? rtrim($resourceKey, 's');

        // Получаем ресурс из роута
        $resource = $request->route($paramName);

        if (!$resource) {
            throw new InvalidArgumentException("Resource parameter '{$paramName}' not found in route.");
        }

        if (!method_exists($resource, 'userCan')) {
            throw new BadMethodCallException('Resource model must use HasResourcePermissions trait');
        }

        // Проверяем доступ
        if (!$resource->userCan($user, $action)) {
            throw new AccessDeniedHttpException(
                "You don't have permission to {$action} this {$resourceKey}."
            );
        }

        return $next($request);
    }
}

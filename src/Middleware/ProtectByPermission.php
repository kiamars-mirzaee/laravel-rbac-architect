<?php

namespace Kiamars\RbacArchitect\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProtectByPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     * @param  string|null  $contextClass
     * @param  string|null  $routeKey
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $permission, ?string $contextClass = null, ?string $routeKey = null): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        $context = null;
        if ($contextClass && $routeKey) {
            $contextId = $request->route($routeKey);
            if ($contextId) {
                $context = $contextClass::find($contextId);
            }
        }

        if (!method_exists($user, 'hasPermissionTo') || !$user->hasPermissionTo($permission, $context)) {
            abort(403, 'Unauthorized access based on project permissions.');
        }

        return $next($request);
    }
}
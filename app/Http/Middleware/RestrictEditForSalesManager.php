<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RestrictEditForSalesManager
{
    /**
     * Block edit/update/delete actions for users with role 'salesmanager',
     * except when the controller belongs to sales.
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $actor = auth('admin')->user();
            if (!$actor) {
                return $next($request);
            }
            $role = strtolower($actor->role ?? '');

            if ($role !== 'salesmanager') {
                return $next($request);
            }

            $route = $request->route();
            $action = $route ? $route->getActionName() : '';

            if (!$action) return $next($request);

            // action format: ControllerClass@method
            if (strpos($action, '@') === false) return $next($request);
            list($controllerClass, $method) = explode('@', $action);

            $controllerClassLower = strtolower($controllerClass);
            $methodLower = strtolower($method);

            // Allow if this is a sales controller (names containing '\\sale' or 'salecontroller')
            if (strpos($controllerClassLower, '\\sale') !== false || strpos($controllerClassLower, 'salecontroller') !== false) {
                return $next($request);
            }

            // Block typical edit/update/delete operations for non-sales controllers
            $isEditAction = str_starts_with($methodLower, 'edit') || str_contains($methodLower, 'update') || str_starts_with($methodLower, 'delete') || in_array($request->method(), ['PUT','PATCH','DELETE']);
            // Also consider route names containing .edit or .update
            $routeName = $route->getName() ?? '';
            if (!$isEditAction && $routeName) {
                $rn = strtolower($routeName);
                $isEditAction = str_contains($rn, '.edit') || str_contains($rn, '.update') || str_contains($rn, '.delete');
            }

            if ($isEditAction) {
                // Deny with 403 and a friendly message
                abort(403, 'You are not authorized to perform edit operations on this resource.');
            }

        } catch (\Throwable $e) {
            Log::warning('RestrictEditForSalesManager middleware error: '.$e->getMessage());
        }

        return $next($request);
    }
}

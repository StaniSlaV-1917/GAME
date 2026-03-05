<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ManagerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, ['manager', 'admin'])) {
            // менеджер + админ проходят, user — нет
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}

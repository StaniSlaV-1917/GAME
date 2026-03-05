<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SpaCors
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $origin = $request->headers->get('Origin');

        if ($origin === 'http://localhost:5173') {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Vary', 'Origin');
        }

        return $response;
    }
}

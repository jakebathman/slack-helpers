<?php

namespace App\Http\Middleware;

use Closure;

class ApiKeyMiddleware
{
    public function handle($request, Closure $next)
    {
        if (! $key = $request->get('key') or $key !== config('app.api_key')) {
            return abort(401, 'API key missing or incorrect.');
        }

        return $next($request);
    }
}

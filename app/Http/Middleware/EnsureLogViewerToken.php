<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLogViewerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) (config('filament-log-viewer.access_token') ?? env('LOG_VIEWER_TOKEN', ''));

        if ($token !== '' && $request->query('token') !== $token) {
            abort(404);
        }

        return $next($request);
    }
}

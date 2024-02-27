<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HeaderMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        if ($request->header('accept') != 'application/vnd.api+json') {
            return response()->json([
                'error' => 'Not aceptable',
                'status' => 406,
                'details' => "Content file not specified"
            ], 406);
        }
        return $next($request);
    }
}

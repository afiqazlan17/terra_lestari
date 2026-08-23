<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && in_array($request->user()->locale, ['ms', 'en'], true)) {
            app()->setLocale($request->user()->locale);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Middleware;

class SetLocale extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('locale') && in_array(session('locale'), ['en', 'lv'])) {
            app()->setLocale(session('locale'));
        } else {
            app()->setLocale(config('app.locale'));
        }
        return $next($request);
    }
}

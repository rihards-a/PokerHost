<?php
// app/Http/Middleware/SetLocale.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        $locale = $request->segment(1);
        $supportedLocales = ['en', 'lv']; // Add your supported locales
        
        if (in_array($locale, $supportedLocales)) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        } else {
            App::setLocale(config('app.locale', 'en'));
        }
        
        return $next($request);
    }
}
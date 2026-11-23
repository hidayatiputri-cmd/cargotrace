<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('app.available_locales', ['en', 'id']);
        $sessionLocale = $request->session()->get('locale');

        if ($sessionLocale && in_array($sessionLocale, $supportedLocales, true)) {
            App::setLocale($sessionLocale);
        } else {
            App::setLocale(config('app.locale', 'en'));
        }

        return $next($request);
    }
}

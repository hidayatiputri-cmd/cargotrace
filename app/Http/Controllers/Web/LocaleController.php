<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Switch active language and store in session.
     */
    public function switch(string $locale, Request $request): RedirectResponse
    {
        $supportedLocales = config('app.available_locales', ['en', 'id']);

        if (!in_array($locale, $supportedLocales, true)) {
            abort(404, 'Unsupported locale.');
        }

        session(['locale' => $locale]);

        return redirect()->back(fallback: route('dashboard'));
    }
}

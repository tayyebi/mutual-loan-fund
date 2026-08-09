<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the locale for every request: the user's own preference first,
 * then their browser's Accept-Language header, then the app default.
 *
 * Display-only. Nothing here changes what timezone or calendar business
 * logic runs on — see .ai/PROJECT.md's "Locale / preferences" section.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('locales.available'));

        $locale = $request->user()?->preferred_locale
            ?? $request->getPreferredLanguage($available)
            ?? config('app.locale');

        App::setLocale($locale);

        return $next($request);
    }
}

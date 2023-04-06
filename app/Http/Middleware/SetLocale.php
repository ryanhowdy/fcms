<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth()->user())
        {
            $locale = Auth()->user()->settings->language;

            App()->setLocale($locale);

            $path   = base_path() . '/lang';
            $domain = 'messages';

            putenv('LC_ALL='.$locale);

            // Call the MoTranslator gettext replacement functions
            _setlocale(LC_MESSAGES, $locale);
            _textdomain($domain);
            _bindtextdomain($domain, $path);
            _bind_textdomain_codeset($domain, 'UTF-8');
        }

        return $next($request);
    }
}

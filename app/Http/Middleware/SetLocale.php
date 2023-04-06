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

            if ($locale !== 'en_US')
            {
                $path   = base_path() . '/lang';
                $domain = 'messages';

                $putenv = putenv('LC_ALL='.$locale);
                if (!$putenv)
                {
                    die('putenv failed');
                }

                $setlocale = setlocale(LC_MESSAGES, $locale);
                if (!$setlocale)
                {
                    die('setlocale failed');
                }

                $bindtextdomain = bindtextdomain($domain, $path);
                if (!$bindtextdomain)
                {
                    die('bindtextdomain failed');
                }

                bind_textdomain_codeset($domain, 'UTF-8');
                textdomain($domain);
            }
        }

        return $next($request);
    }
}

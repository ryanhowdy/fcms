<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LegacyProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
//        require_once app_path() . '/Legacy/inc/thirdparty/php-gettext/gettext.inc';
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}

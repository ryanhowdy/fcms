<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\NavigationLink;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('layouts.sidebar', function ($view) {
            $navigationLinks = NavigationLink::get();

            $links = [];

            foreach ($navigationLinks as $k => $link)
            {
                $links[$link->group][$link->order] = $link->toArray();

                $links[$link->group][$link->order]['icon'] = $link->icon;
            }

            $view->with('links', $links);
        });
    }
}

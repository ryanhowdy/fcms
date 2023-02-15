<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\NavigationLink;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;

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
        Paginator::useBootstrap();

        View::composer('layouts.sidebar', function ($view) {
            $navigationLinks = NavigationLink::get();

            $links = [];

            foreach ($navigationLinks as $k => $link)
            {
                // Check admin gate for admin links
                if ($link->group == 5 && !Gate::allows('administrate'))
                {
                    continue;
                }

                $links[$link->group][$link->order] = $link->toArray();

                $links[$link->group][$link->order]['icon'] = $link->icon;
            }

            $view->with('links', $links);
        });
    }
}

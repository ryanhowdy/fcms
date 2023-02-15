<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Gate : Administrate
        Gate::define('administrate', function(User $user) {
            if ($user->access == 1)
            {
                return true;
            }

            return false;
        });

        // Gate : Help
        Gate::define('help', function(User $user) {
            if ($user->access <= 2)
            {
                return true;
            }

            return false;
        });

        // Gate : Guest
        Gate::define('guest', function(User $user) {
            if ($user->access == 10)
            {
                return true;
            }

            return false;
        });
    }
}

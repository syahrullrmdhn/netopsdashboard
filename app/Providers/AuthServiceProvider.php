<?php

namespace App\Providers;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider; // ← this alias is crucial
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerPolicies();

        // allow admins to do everything
        Gate::before(function (User $user, $ability) {
            return $user->is_admin ? true : null;
        });

        // define named abilities if you want more granularity later
        Gate::define('manage customers', fn(User $user) => $user->is_admin);
        Gate::define('manage tickets',   fn(User $user) => $user->is_admin);
        Gate::define('view reports',     fn(User $user) => $user->is_admin);
        Gate::define('view sla',         fn(User $user) => $user->is_admin);
        Gate::define('view performance', fn(User $user) => $user->is_admin);
    }
}
<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Security module: only Root manages users, and no one manages the Root account through the interface.
        Gate::define('manage-users', fn (User $user): bool => $user->ativo && $user->isRoot());
        Gate::define('manage-user', fn (User $user, User $managed): bool => $user->ativo && $user->isRoot() && ! $managed->isRoot());
    }
}

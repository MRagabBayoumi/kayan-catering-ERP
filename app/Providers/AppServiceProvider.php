<?php

namespace App\Providers;

use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
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
        // Global Company Settings View Composer
        View::composer('*', function ($view) {
            static $settings = null;
            static $hasChecked = false;

            if (! $hasChecked) {
                try {
                    if (Schema::hasTable('company_settings')) {
                        $settings = CompanySetting::getSettings();
                    }
                } catch (\Throwable $e) {
                    // Silently fallback if database is not reachable during early boot
                }
                $hasChecked = true;
            }

            $view->with('globalCompanySetting', $settings);
        });

        // Super Admin Bypass
        Gate::before(function (User $user) {
            if ($user->isAdmin()) {
                return true;
            }
        });

        // 1. Warehouse Department Gates
        Gate::define('manage-warehouse', function (User $user) {
            return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_STORE_MANAGER, User::ROLE_STOREKEEPER]);
        });

        Gate::define('create-dispatch', function (User $user) {
            return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_STORE_MANAGER]);
        });

        Gate::define('perform-blind-audit', function (User $user) {
            return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_STOREKEEPER]);
        });

        // 2. Costing Department Gates
        Gate::define('manage-costing', function (User $user) {
            return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_COSTING_MANAGER]);
        });

        // 3. Sales & Tenders Department Gates
        Gate::define('manage-sales', function (User $user) {
            return in_array($user->role, [User::ROLE_ADMIN]);
        });

        // 4. Warehouse Accountant & Settlements Gates
        Gate::define('manage-settlements', function (User $user) {
            return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_ACCOUNTANT]);
        });

        Gate::define('review-blind-audit', function (User $user) {
            return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_ACCOUNTANT]);
        });
    }
}

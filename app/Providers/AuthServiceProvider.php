<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Budget\Entities\MasterBudget;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('access_departments', function ($user) {
        // Contoh: hanya user dengan role admin bisa akses
        return true;
        // Atau sesuaikan dengan logic Anda
        });
        Gate::define('approve-budget', function ($user, MasterBudget $budget) {
        return in_array($user->role, ['manager', 'finance']); 
        });

        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}

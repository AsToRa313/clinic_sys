<?php

namespace App\Providers;
use Illuminate\Support\Facades\Gate;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // app/Providers/AuthServiceProvider.php




    $this->registerPolicies();

    Gate::define('is-doctor', function ($user) {
        return $user->role === 'doctor';
    });
    Gate::define('is-admin', function ($user) {
        return $user->role === 'admin';
    });
    Gate::define('is-patient', function ($user) {
        return $user->role === 'patient';
    });
    Gate::define('is-receptionist', function ($user) {
        return $user->role === 'receptionist';
    });
    
    


        //
    }
}

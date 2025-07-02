<?php

namespace App\Providers;

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
        //
        \DB::listen(function ($query) {
            \Log::info($query->sql);
            \Log::info($query->bindings);
        });
        \DB::listen(function ($query) {
            logger('SQL: ' . $query->sql);
            logger('Bindings: ' . json_encode($query->bindings));
        });
        
        
    }
}

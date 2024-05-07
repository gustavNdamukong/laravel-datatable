<?php

namespace Gustocoder\LaravelDatatable;

use Illuminate\Support\ServiceProvider;

class LaravelDatatableServiceProvider extends ServiceProvider
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
        $namespace = "laravel-datatable";
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views/', $namespace);

        $this->publishes([
            __DIR__.'/../config/laravel-datatable.php' => config_path('laravel-datatable.php'),
        ]);

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/laravel-datatable'),
        ], 'public');
    }
}

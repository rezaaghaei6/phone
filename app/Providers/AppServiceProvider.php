<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        \Illuminate\Support\Facades\Schema::defaultStringLength(191);
        \Illuminate\Support\Facades\Auth::extend('phone', function ($app, $name, array $config) {
            return new \Illuminate\Auth\SessionGuard(
                $name,
                new \Illuminate\Auth\EloquentUserProvider($app['hash'], User::class),
                $app['session.store']
            );
        });
    }
}
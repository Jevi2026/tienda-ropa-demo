<?php

namespace App\Providers;

use App\Support\Cart;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Cart::class);
    }

    public function boot(): void
    {
        View::composer('layouts.store', function ($view) {
            $view->with('cartCount', app(Cart::class)->count());
        });
    }
}

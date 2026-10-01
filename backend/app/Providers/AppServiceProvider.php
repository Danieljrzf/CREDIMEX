<?php

namespace App\Providers;

use App\Auth\ApiSesionAutenticador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        Auth::viaRequest('sesion_token', function (Request $request): mixed {
            return app(ApiSesionAutenticador::class)->authenticate($request);
        });
    }
}

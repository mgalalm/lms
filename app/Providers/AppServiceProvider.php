<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
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
        // Inertia props are consumed directly by the frontend, not as a
        // JSON:API response, so resources shouldn't auto-wrap in "data".
        JsonResource::withoutWrapping();
    }
}

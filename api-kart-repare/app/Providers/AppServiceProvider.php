<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

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
        // Enregistrer les policies
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(\App\Models\Pilot::class, \App\Policies\PilotPolicy::class);
        Gate::policy(\App\Models\Kart::class, \App\Policies\KartPolicy::class);
        Gate::policy(\App\Models\RequestStatus::class, \App\Policies\RequestStatusPolicy::class);
        Gate::policy(\App\Models\RepairRequest::class, \App\Policies\RepairRequestPolicy::class);
        Gate::policy(\App\Models\Product::class, \App\Policies\ProductPolicy::class);

        // Configuration Sanctum pour SPA
        Sanctum::usePersonalAccessTokenModel(\Laravel\Sanctum\PersonalAccessToken::class);

        // Durée d'expiration des tokens (optionnel)
        // Sanctum::actingAs($user, ['*']);
    }
}

<?php

namespace App\Providers;

use App\Models\BloodRequest;
use App\Policies\BloodRequestPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Enregistrement de la policy pour BloodRequest
        Gate::policy(BloodRequest::class, BloodRequestPolicy::class);
    }
}

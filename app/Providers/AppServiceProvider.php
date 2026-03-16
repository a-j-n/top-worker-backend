<?php

namespace App\Providers;

use App\Models\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider as BaseTelescopeServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(BaseTelescopeServiceProvider::class)) {
            $this->app->register(BaseTelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-admin-api', fn (User $user): bool => $user->isAdmin());

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            $schemeName = 'sanctumBearerAuth';

            $openApi->components->addSecurityScheme(
                $schemeName,
                SecurityScheme::http('bearer', 'Bearer')
                    ->setDescription('Use the Sanctum token returned by POST /api/admin/login as Authorization: Bearer {token}.')
            );

            foreach ($openApi->paths as $path) {
                if (! str_starts_with($path->path, 'admin/') || $path->path === 'admin/login') {
                    continue;
                }

                foreach ($path->operations as $operation) {
                    $operation->addSecurity(new SecurityRequirement([$schemeName => []]));
                }
            }
        });
    }
}

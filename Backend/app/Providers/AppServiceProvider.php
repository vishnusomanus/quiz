<?php

namespace App\Providers;

use App\Contracts\Repository\UserRepositoryInterface;
use App\OpenApi\SwaggerAnalyser;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::$clientUuids = false;
        Passport::personalAccessTokensExpireIn(
            now()->addSeconds((int) config('passport.tokens.expire_in', 3600)),
        );

        config([
            'l5-swagger.defaults.scanOptions.analyser' => new SwaggerAnalyser,
        ]);
    }
}

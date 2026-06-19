<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\CityRepositoryInterface;
use App\Repositories\Eloquent\CityRepository;
use App\Repositories\Contracts\WeatherRepositoryInterface;
use App\Repositories\Eloquent\WeatherRepository;
use App\Repositories\Contracts\DestinationRepositoryInterface;
use App\Repositories\Eloquent\DestinationRepository;
use App\Repositories\Contracts\RecommendationRepositoryInterface;
use App\Repositories\Eloquent\RecommendationRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CityRepositoryInterface::class, CityRepository::class);
        $this->app->bind(WeatherRepositoryInterface::class, WeatherRepository::class);
        $this->app->bind(DestinationRepositoryInterface::class, DestinationRepository::class);
        $this->app->bind(RecommendationRepositoryInterface::class, RecommendationRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}

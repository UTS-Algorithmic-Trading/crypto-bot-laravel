<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //Paginator::useBootstrapThree();
        Paginator::useBootstrap();
        Schema::defaultStringLength(191);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //https://stackoverflow.com/questions/51017333/binding-the-dependency-of-a-laravel-service-provider-inside-the-provider-itself
        $this->app->bind(TwitterFeedServiceProvider::class, function ($app) {
            return new TwitterFeedServiceProvider($app);
        });

        $this->app->bind(SocialSentimentServiceProvider::class, function ($app) {
            return new SocialSentimentServiceProvider($app);
        });
    }
}

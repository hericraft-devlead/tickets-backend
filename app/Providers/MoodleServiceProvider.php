<?php

namespace App\Providers;

use App\Services\MoodleService;
use Illuminate\Support\ServiceProvider;

class MoodleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(MoodleService::class, function ($app) {
            return new MoodleService();
        });
    }

    public function boot()
    {
        //
    }
}
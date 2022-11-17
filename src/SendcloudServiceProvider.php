<?php

namespace Todocoding\Sendcloud;

use Illuminate\Support\ServiceProvider;

class SendcloudServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/sendcloud.php' => config_path('sendcloud.php'),
        ]);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('sendcloud', function () {
            return new Sendcloud();
        });
    }
}

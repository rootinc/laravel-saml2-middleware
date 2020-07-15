<?php

namespace RootInc\LaravelAzureMiddleware;

use Illuminate\Support\ServiceProvider;

class Saml2ServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole())
        {
            $this->publishes([
                __DIR__ . '/../config/saml2.php' => config_path('saml2.php'),
            ], 'saml2-config');
        }
        else
        {
            $this->mergeConfigFrom(
                __DIR__ . '/../config/saml2.php', 'saml2'
            );
        }
    }
}
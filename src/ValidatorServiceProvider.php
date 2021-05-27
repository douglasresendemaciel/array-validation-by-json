<?php

namespace NoCartorio\ArrayValidationByJson;

use Illuminate\Support\ServiceProvider;

class ValidatorServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([__DIR__ . '/config/nocartorio-validate-rules-json.php' => config_path('nocartorio-validate-rules-json.php')]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/nocartorio-validate-rules-json.php', 'nocartorio-validate-rules-json');
    }
}

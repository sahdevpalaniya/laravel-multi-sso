<?php
namespace Sahdev\SSO;

use Illuminate\Support\ServiceProvider;
use Sahdev\SSO\Services\SSOManager;

class SSOServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/sso.php', 'sso');

        $this->app->singleton('sso', function () {
            return new SSOManager();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/sso.php' => config_path('sso.php'),
        ], 'sso-config');
    }
}

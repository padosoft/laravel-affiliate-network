<?php

namespace Padosoft\AffiliateNetwork;

use Illuminate\Support\ServiceProvider;
use Padosoft\AffiliateNetwork\Networks\Zanox;
use Padosoft\AffiliateNetwork\Networks\ZanoxEx;

class AffiliateNetworkServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/config.php' => config_path('laravel-affiliate-network.php'),
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(Connection::class, function ($app) {
            return new NetworkManager();
        });
        $this->app->alias('NetworkManager', NetworkManager::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
            'NetworkManager'
        ];
    }
}

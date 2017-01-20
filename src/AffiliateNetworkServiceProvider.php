<?php

namespace Padosoft\AffiliateNetwork;

use Illuminate\Support\ServiceProvider;

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
        $this->app['NetworkManager']=$this->app->share(function (NetworkInterface $network) {
            return new NetworkManager($network);
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
            'NetworkManager',
        ];
    }
}

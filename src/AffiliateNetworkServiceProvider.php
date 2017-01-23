<?php

namespace Padosoft\AffiliateNetwork;

use Illuminate\Support\ServiceProvider;
use Padosoft\AffiliateNetwork\Networks\Zanox;

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
        $this->app['ZanoxNetwork']=$this->app->share(function ($app) {
            return new Zanox('','');
        });
        $this->app['ZanoxNetworkManager']=$this->app->share(function ($app) {
            return new NetworkManager($app['ZanoxNetwork']);
        });
        $this->app->alias('ZanoxNetworkManager', NetworkManager::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
            'ZanoxNetworkManager','ZanoxNetwork'
        ];
    }
}

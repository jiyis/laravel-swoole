<?php

namespace Jiyis;


use Jiyis\Server\BaseServer;

class LaravelServiceProvider extends SwooleServiceProvider
{

    /**
     * Register manager.
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton('swoole.http', function ($app) {
            return new BaseServer($app, 'laravel');
        });
    }

    /**
     * Boot routes.
     *
     * @return void
     */
    protected function bootRoutes()
    {
        require __DIR__.'/../routes/laravel_routes.php';
    }
}
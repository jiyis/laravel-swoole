<?php

namespace Jiyis;



use Jiyis\Server\BaseServer;

class LumenServiceProvider extends SwooleServiceProvider
{

    /**
     * Register manager.
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton('swoole.http', function ($app) {
            return new BaseServer($app, 'lumen');
        });
    }

    /**
     * Boot routes.
     *
     * @return void
     */
    protected function bootRoutes()
    {
        $app = $this->app;

        if (property_exists($app, 'router')) {
            $app->router->group(['namespace' => 'Jiyis\Controllers'], function ($app) {
                require __DIR__ . '/../routes/lumen_routes.php';
            });
        } else {
            $app->group(['namespace' => 'App\Http\Controllers'], function ($app) {
                require __DIR__ . '/../routes/lumen_routes.php';
            });
        }
    }
}
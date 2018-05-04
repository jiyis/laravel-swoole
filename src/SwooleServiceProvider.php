<?php

namespace Jiyis;


use Illuminate\Support\ServiceProvider;
use Jiyis\Console\SwooleCommand;

class SwooleServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigs();
        $this->registerManager();
        $this->registerCommands();
    }

    /**
     * Register manager.
     *
     * @return void
     */
    abstract protected function registerManager();

    /**
     * Boot routes.
     *
     * @return void
     */
    abstract protected function bootRoutes();

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/swoole.php' => base_path('config/swoole.php'),
        ]);
        $this->publishes([
            __DIR__ . '/../config/swoole.php'    => base_path('config/swoole.php'),
            __DIR__ . '/../config/websocket.php' => base_path('config/websocket.php'),
            __DIR__ . '/../routes/websocket.php' => base_path('routes/websocket.php')
        ], 'swoole');
        $this->bootRoutes();
    }

    /**
     * Merge configurations.
     */
    protected function mergeConfigs()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/swoole.php', 'swoole'
        );
        $this->mergeConfigFrom(
            __DIR__ . '/../config/websocket.php', 'websocket'
        );
    }

    /**
     * Register commands.
     */
    protected function registerCommands()
    {
        $this->commands([
            SwooleCommand::class,
        ]);
    }

}
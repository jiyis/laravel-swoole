<?php

namespace Jiyis\Server;

use Illuminate\Contracts\Container\Container;
use Jiyis\Illuminate\Application;
use Jiyis\Traits\BindListeners;
use Jiyis\Traits\EnableInotify;
use Jiyis\Traits\EnableSwooleTable;
use Jiyis\Traits\EnableWebsocket;
use Swoole\Http\Server as HttpServer;
use Swoole\WebSocket\Server as WebSocketServer;

class BaseServer
{
    use EnableWebsocket, EnableSwooleTable, BindListeners, EnableInotify;

    const MAC_OSX = 'Darwin';

    /**
     * @var \Swoole\Http\Server | \Swoole\Websocket\Server
     */
    protected $server;

    /**
     * Container.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * @var \Jiyis\Illuminate\Application
     */
    protected $application;

    /**
     * Laravel|Lumen Application.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app;

    /**
     * @var string
     */
    protected $framework;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var boolean
     */
    protected $isSandbox;

    /**
     * @var \Jiyis\Illuminate\Sandbox
     */
    protected $sandbox;


    /**
     * HTTP server manager constructor.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @param string $framework
     * @param string $basePath
     */
    public function __construct(Container $container, $framework, $basePath = null)
    {
        $this->container = $container;
        $this->framework = $framework;
        $this->basePath = $basePath;

        $this->initialize();
    }

    /**
     * Run swoole server.
     */
    public function run()
    {
        $this->server->start();
    }

    /**
     * Stop swoole server.
     */
    public function stop()
    {
        $this->server->shutdown();
    }

    /**
     * Initialize.
     */
    protected function initialize()
    {
        $this->setProcessName('manager process');

        $this->setIsSandbox();
        $this->createTables();
        $this->prepareWebsocket();
        $this->createSwooleServer();
        $this->configureSwooleServer();
        $this->setSwooleServerListeners();
        $this->addInotifyProcess();
    }

    /**
     * Prepare settings if websocket is enabled.
     */
    protected function prepareWebsocket()
    {
        $isWebsocket = $this->container['config']->get('swoole.websocket.enabled');
        $parser = $this->container['config']->get('swoole_websocket.parser');

        if ($isWebsocket) {
            array_push($this->events, ...$this->wsEvents);
            $this->isWebsocket = true;
            $this->setParser(new $parser);
            $this->setWebsocketRoom();
        }
    }

    /**
     * Create swoole server.
     */
    protected function createSwooleServer()
    {
        $server = $this->isWebsocket ? WebsocketServer::class : HttpServer::class;
        $host = $this->container['config']->get('swoole.server.host');
        $port = $this->container['config']->get('swoole.server.port');

        $this->server = new $server($host, $port);
    }

    /**
     * Set swoole server configurations.
     */
    protected function configureSwooleServer()
    {
        $config = $this->container['config']->get('swoole.server');

        $this->server->set($config);
    }

    /**
     * Set swoole server listeners.
     */
    protected function setSwooleServerListeners()
    {
        foreach ($this->events as $event) {
            $listener = 'on' . ucfirst($event);

            if (method_exists($this, $listener)) {
                $this->server->on($event, [$this, $listener]);
            } else {
                $this->server->on($event, function () use ($event) {
                    $event = sprintf('swoole.%s', $event);

                    $this->container['events']->fire($event, func_get_args());
                });
            }
        }
    }


    /**
     * Create application.
     */
    protected function createApplication()
    {
        return $this->application = Application::make($this->framework, $this->basePath);
    }

    /**
     * Get application.
     *
     * @return \Jiyis\Illuminate\Application
     */
    protected function getApplication()
    {
        if (!$this->application instanceof Application) {
            $this->createApplication();
        }

        return $this->application;
    }

    /**
     * Set bindings to Laravel app.
     */
    protected function bindToLaravelApp()
    {
        $this->bindSwooleServer();
        $this->bindSwooleTable();

        if ($this->isWebsocket) {
            $this->bindRoom();
            $this->bindWebsocket();
        }
    }

    /**
     * Set isSandbox config.
     */
    protected function setIsSandbox()
    {
        $this->isSandbox = $this->container['config']->get('swoole.sandbox_mode', false);
    }

    /**
     * Set Laravel app.
     */
    protected function setLaravelApp()
    {
        $this->app = $this->getApplication()->getApplication();
    }

    /**
     * Bind swoole server to Laravel app container.
     */
    protected function bindSwooleServer()
    {
        $this->app->singleton('swoole.server', function () {
            return $this->server;
        });
    }

    /**
     * Log server error.
     * @param \Exception $e
     */
    public function logServerError(\Exception $e)
    {
        $logFile = $this->container['config']->get('swoole.server.log_file');

        try {
            $output = fopen($logFile, 'w');
        } catch (\Exception $e) {
            $output = STDOUT;
        }

        $prefix = sprintf("[%s #%d *%d]\tERROR\t", date('Y-m-d H:i:s'), $this->server->master_pid, $this->server->worker_id);

        fwrite($output, sprintf('%s%s(%d): %s', $prefix, $e->getFile(), $e->getLine(), $e->getMessage()) . PHP_EOL);
    }

}
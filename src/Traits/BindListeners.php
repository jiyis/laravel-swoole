<?php

namespace Jiyis\Traits;


use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Facade;
use Jiyis\Illuminate\Websocket\Websocket;
use Jiyis\Server\Sandbox;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Jiyis\Illuminate\Http\Response;
use Swoole\Server;

trait BindListeners
{

    /**
     * swoole 中所有的回调函数.
     *
     * @var array
     */
    protected $events = [
        'start', 'shutDown', 'workerStart', 'workerStop', 'packet',
        'bufferFull', 'bufferEmpty', 'task', 'finish', 'pipeMessage',
        'workerError', 'managerStart', 'managerStop', 'request',
    ];

    /**
     * "onStart" listener.
     * @param Server $server
     */
    public function onStart(Server $server)
    {
        $prefix = $this->getProcessPrefix();
        $this->setProcessName(sprintf('%s swoole: master process', $prefix));
        $this->createPidFile();

        $this->container['events']->fire('swoole.start', func_get_args());
    }

    /**
     * "onManagerStart" listener.
     * @param Server $server
     */
    public function onManagerStart(Server $server)
    {
        $prefix = $this->getProcessPrefix();
        $this->setProcessName(sprintf('%s swoole: manager process', $prefix));

        $this->container['events']->fire('swoole.managerStart', func_get_args());
    }

    /**
     * "onWorkerStart" listener.
     * @param Server $server
     * @param $workerId
     */
    public function onWorkerStart(Server $server, $workerId)
    {

        if ($workerId >= $server->setting['worker_num']) {
            $type = 'taskWorker';
        } else {
            $type = 'worker';
        }
        $prefix = $this->getProcessPrefix();
        $this->setProcessName(sprintf('%s swoole: %s process %d', $prefix, $type, $workerId));

        $this->clearCache();

        $this->container['events']->fire(sprintf('swoole.%sStart', $type), func_get_args());

        // don't init laravel app in task workers
        if ($server->taskworker) {
            return;
        }

        // clear events instance in case of repeated listeners in worker process
        Facade::clearResolvedInstance('events');

        // initialize laravel app
        $this->createApplication();
        $this->setLaravelApp();

        // bind after setting laravel app
        $this->bindToLaravelApp();

        // set application to sandbox environment
        if ($this->isSandbox) {
            $this->sandbox = Sandbox::make($this->getApplication());
        }

        // load websocket handlers after binding websocket to laravel app
        if ($this->isWebsocket) {
            $this->setWebsocketHandler();
            $this->loadWebsocketRoutes();
        }
    }

    /**
     * "onRequest" listener.
     * @param SwooleRequest $swooleRequest
     * @param SwooleResponse $swooleResponse
     */
    public function onRequest(SwooleRequest $swooleRequest, SwooleResponse $swooleResponse)
    {
        $this->app['events']->fire('swoole.request');

        $application = $this->getApplication();

        // transform swoole request to illuminate request
        $illuminateRequest = Request::make($swooleRequest)->toIlluminate();

        try {

            // use cloned application if sandbox mode is on
            if ($this->isSandbox) {
                $application->getApplication()->instance('request', $illuminateRequest);
                $application = $this->sandbox->getApplication();
                $this->sandbox->enable();
            }

            // bind illuminate request to laravel/lumen
            $application->getApplication()->instance('request', $illuminateRequest);
            Facade::clearResolvedInstance('request');

            // fire event
            $this->container['events']->fire('swoole.requestStart', func_get_args());

            // handle request via laravel/lumen's dispatcher
            $illuminateResponse = $application->run($illuminateRequest);

            // clear request
            $this->resetOnRequest();

            $response = Response::make($illuminateResponse, $swooleResponse);
            $response->send();

            // disable and recycle sandbox resource
            if ($this->isSandbox) {
                $this->sandbox->disable();
            }
            // fire event
            $this->container['events']->fire('swoole.requestEnd', func_get_args());

        } catch (\Exception $e) {
            try {
                $exceptionResponse = $this->app[ExceptionHandler::class]->render($illuminateRequest, $e);
                $response = Response::make($exceptionResponse, $swooleResponse);
                $response->send();
            } catch (\Exception $e) {
                $this->logServerError($e);
            }
        }
    }

    /**
     * Set onTask listener.
     * @param Server $server
     * @param $taskId
     * @param $fromId
     * @param $data
     */
    public function onTask(Server $server, $taskId, $fromId, $data)
    {
        $this->container['events']->fire('swoole.task', func_get_args());

        try {
            // push websocket message
            if ($this->isWebsocket
                && array_key_exists('action', $data)
                && $data['action'] === Websocket::PUSH_ACTION) {
                $this->pushMessage($server, $data['data'] ?? []);
            }
        } catch (\Exception $e) {
            $this->logServerError($e);
        }
    }

    /**
     * @param Server $server
     */
    public function onManagerStop(Server $server)
    {

    }

    /**
     * @param Server $server
     * @param $workerId
     */
    public function onWorkerStop(Server $server, $workerId)
    {

    }

    /**
     * @param Server $server
     * @param $workerId
     * @param $workerPId
     * @param $exitCode
     * @param $signal
     */
    public function onWorkerError(Server $server, $workerId, $workerPId, $exitCode, $signal)
    {

    }

    /**
     * @param Server $server
     * @param $taskId
     * @param $data
     */
    public function onFinish(Server $server, $taskId, $data)
    {
        // task worker callback
    }

    /**
     * Set onShutdown listener.
     */
    public function onShutdown()
    {
        $this->removePidFile();

        $this->app['events']->fire('swoole.shutdown', func_get_args());
    }

    /**
     * Set process name.
     *
     * @param $process
     */
    protected function setProcessName($process)
    {
        if (PHP_OS === static::MAC_OSX) {
            return;
        }
        $serverName = 'swoole_server';
        $appName = $this->container['config']->get('app.name', 'Laravel');

        $name = sprintf('%s: %s for %s', $serverName, $process, $appName);
        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($name);
        } elseif (function_exists('\swoole_set_process_name')) {
            \swoole_set_process_name($name);
        }
    }

    /**
     * Gets pid file path.
     *
     * @return string
     */
    protected function getPidFile()
    {
        return $this->container['config']->get('swoole.server.pid_file');
    }

    /**
     * Create pid file.
     */
    protected function createPidFile()
    {
        $pidFile = $this->getPidFile();
        $pid = $this->server->master_pid;

        file_put_contents($pidFile, $pid);
    }

    /**
     * Remove pid file.
     */
    protected function removePidFile()
    {
        unlink($this->getPidFile());
    }

    /**
     * Clear APC or OPCache.
     */
    protected function clearCache()
    {
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        clearstatcache();
    }

    /**
     * Reset on every request.
     */
    protected function resetOnRequest()
    {
        // Reset websocket data
        if ($this->isWebsocket) {
            $this->websocket->reset(true);
        }

        if ($this->isSandbox) {
            return;
        }

        // 清除文件状态缓存
        clearstatcache();
        // Clear user sessions
        $this->getApplication()->clearSessions();
        // Reset user-customized providers
        $this->getApplication()->resetProviders();
        // Clear user-customized facades
        $this->getApplication()->clearFacades();
        // Clear user-customized instances
        $this->getApplication()->clearInstances();
    }

    /**
     * Get process prefix
     * @return mixed
     */
    protected function getProcessPrefix()
    {
        return $this->container['config']->get('swoole.process_prefix');
    }


}
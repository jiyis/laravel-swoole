<?php

namespace Jiyis\Console;


use Illuminate\Console\Command;
use Swoole\Process;

class SwooleCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Laravel swoole server console.';

    /**
     * The console command action. start|stop|restart|reload
     *
     * @var string
     */
    protected $actions;

    /**
     * The configs for swoole.
     *
     * @var array
     */
    protected $config;

    /**
     * The pid.
     *
     * @var int
     */
    protected $pid;

    /**
     * SwooleCommand constructor.
     */
    public function __construct()
    {
        $this->actions   = ['start', 'stop', 'restart', 'reload', 'publish', 'infos'];
        $actions         = implode('|', $this->actions);
        $this->signature .= sprintf(' {action : %s}', $actions);
        $this->config    = config('swoole');

        parent::__construct();
    }

    /**
     * 兼容旧版本的laravel,5.5已经改为handle
     */
    public function fire()
    {
        $this->handle();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $action = (string)$this->argument('action');

        if (!in_array($action, $this->actions)) {
            $this->error(sprintf("Invalid argument %s. Only support %s.", $action, implode(',', $this->actions)));
            exit(1);
        }

        $this->{$action}();
    }

    protected function start()
    {
        $this->outputLogo();

        if ($this->isRunning($this->getPid())) {
            $this->error('Failed! Swoole http server process is already running.');
            exit(1);
        }

       /* $basePath = empty($this->config['laravel_base_path']) ? base_path() : $this->config['laravel_base_path'];
        if (empty($this->config['swoole']['document_root'])) {
            $this->config['swoole']['document_root'] = $basePath . '/public';
        }
        if (empty($this->config['process_prefix'])) {
            $this->config['process_prefix'] = $basePath;
        }*/
        if (!empty($this->config['events'])) {
            if (empty($this->config['server']['task_worker_num']) || $this->config['server']['task_worker_num'] <= 0) {
                $this->error('Swoole: Asynchronous event listening needs to set task_worker_num > 0');
                return;
            }
        }


        $host = $this->config['server']['host'];
        $port = $this->config['server']['port'];

        $this->info('Starting swoole http server...');
        $this->info("Swoole http server started: <http://{$host}:{$port}>");
        if ($this->isDaemon()) {
            $this->info('> (You can run this command to ensure the ' .
                'swoole http server process is running: ps aux|grep "swoole")');
        }

        $this->getLaravel()->make('swoole.http')->run();
    }

    /**
     * If Swoole process is running.
     *
     * @param int $pid
     * @return bool
     */
    protected function isRunning($pid)
    {
        if (! $pid) {
            return false;
        }
        //杀死pid
        Process::kill($pid, 0);

        //获取最近一次系统调用的错误码，
        return ! swoole_errno();
    }

    /**
     * Get Pid file path.
     *
     * @return string
     */
    protected function getPidPath()
    {
        return $this->config['server']['pid_file'];
    }

    /**
     * Return daemonize config.
     */
    protected function isDaemon()
    {
        return $this->configs['server']['daemonize'];
    }

    /**
     * Get pid file.
     *
     * @return int|null
     */
    public function getPid()
    {
        if ($this->pid) {
            return $this->pid;
        }

        $pid  = null;
        $path = $this->getPidPath();

        if (file_exists($path)) {
            $pid = (int)file_get_contents($path);

            if (!$pid) {
                $this->removePidFile();
            } else {
                $this->pid = $pid;
            }
        }

        return $this->pid;
    }


    /**
     * Remove Pid file.
     */
    protected function removePidFile()
    {
        if (file_exists($this->getPidPath())) {
            unlink($this->getPidPath());
        }
    }

}
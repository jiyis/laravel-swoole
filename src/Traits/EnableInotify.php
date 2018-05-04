<?php

namespace Jiyis\Traits;


use Jiyis\Server\Inotify;
use Swoole\Process;

trait EnableInotify
{

    /**
     * Add inotify process.
     */
    public function addInotifyProcess()
    {

        $conf = $this->getApplication()->getApplication()['config'];
        if (empty($conf['inotify_reload']['enable'])) {
            return;
        }

        if (!extension_loaded('inotify')) {
            $this->logServerError('require extension inotify', 'WARN');
            return;
        }

        $log = !empty($conf['inotify_reload']['log']);
        $fileTypes = isset($conf['inotify_reload']['file_types']) ? (array)$conf['inotify_reload']['file_types'] : [];
        $autoReload = function () use ($fileTypes, $log, $conf) {
            $this->setProcessTitle(sprintf('%s laravels: inotify process', $conf['process_prefix']));
            $inotify = new Inotify(base_path(), IN_CREATE | IN_MODIFY | IN_DELETE, function ($event) use ($log) {
                $this->server->reload();
                if ($log) {
                    $this->logServerError(sprintf('reloaded by inotify, file: %s', $event['name']));
                }
            });
            $inotify->addFileTypes($fileTypes);
            $inotify->watch();
            if ($log) {
                $this->logServerError(sprintf('count of watched files by inotify: %d', $inotify->getWatchedFileCount()));
            }
            $inotify->start();
        };

        $inotifyProcess = new Process($autoReload, false, false);
        $this->swoole->addProcess($inotifyProcess);
    }
}
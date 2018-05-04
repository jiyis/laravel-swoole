<?php

namespace Swoole\Laravel\Illuminate\Facades;


use Illuminate\Support\Facades\Facade;

class Server extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'swoole.server';
    }
}
<?php

namespace Jiyis\Illuminate\Websocket\SocketIO;

use Swoole\Websocket\Frame;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Jiyis\Illuminate\Websocket\HandlerContract;

class WebsocketHandler implements HandlerContract
{
    /**
     * "onOpen" listener.
     * @param int $fd
     * @param Request $request
     * @return bool
     */
    public function onOpen($fd, Request $request)
    {
        if (!$request->input('sid')) {
            $payload = json_encode([
                'sid'          => base64_encode(uniqid()),
                'upgrades'     => [],
                'pingInterval' => Config::get('swoole_websocket.ping_interval'),
                'pingTimeout'  => Config::get('swoole_websocket.ping_timeout')
            ]);
            $initPayload = Packet::OPEN . $payload;
            $connectPayload = Packet::MESSAGE . Packet::CONNECT;

            app('swoole.server')->push($fd, $initPayload);
            app('swoole.server')->push($fd, $connectPayload);

            return true;
        }

        return false;
    }

    /**
     * "onMessage" listener.
     *  only triggered when event handler not found
     * @param Frame $frame
     */
    public function onMessage(Frame $frame)
    {
        //
    }

    /**
     * "onClose" listener.
     * @param int $fd
     * @param int $reactorId
     */
    public function onClose($fd, $reactorId)
    {
        //
    }
}

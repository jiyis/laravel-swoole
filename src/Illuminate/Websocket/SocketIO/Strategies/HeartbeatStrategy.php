<?php

namespace Jiyis\Illuminate\Websocket\SocketIO\Strategies;

use Swoole\Websocket\Frame;
use Swoole\Websocket\Server;
use Jiyis\Illuminate\Websocket\SocketIO\Packet;

class HeartbeatStrategy
{
    /**
     * If return value is true will skip decoding.
     * @param Server $server
     * @param Frame $frame
     * @return bool
     */
    public function handle(Server $server, Frame $frame)
    {
        $packet = $frame->data;
        $packetLength = strlen($packet);
        $payload = '';

        if (Packet::getPayload($packet)) {
            return false;
        }

        if ($isPing = Packet::isSocketType($packet, 'ping')) {
            $payload .= Packet::PONG;
        }

        if ($isPing && $packetLength > 1) {
            $payload .= substr($packet, 1, $packetLength - 1);
        }

        if ($isPing) {
            $server->push($frame->fd, $payload);
        }

        return true;
    }
}

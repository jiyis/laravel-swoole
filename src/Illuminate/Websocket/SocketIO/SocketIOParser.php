<?php

namespace Jiyis\Illuminate\Websocket\SocketIO;

use Swoole\Websocket\Frame;
use Jiyis\Illuminate\Websocket\Parser;
use Jiyis\Illuminate\Websocket\SocketIO\Strategies\HeartbeatStrategy;

class SocketIOParser extends Parser
{
    /**
     * Strategy classes need to implement handle method.
     */
    protected $strategies = [
        HeartbeatStrategy::class
    ];

    /**
     *  Encode output message for websocket push.
     * @param $event
     * @param $data
     * @return string
     */
    public function encode($event, $data)
    {
        $packet = Packet::MESSAGE . Packet::EVENT;
        $shouldEncode = is_array($data) || is_object($data);
        $data = $shouldEncode ? json_encode($data) : $data;
        $format = $shouldEncode ? '["%s",%s]' : '["%s","%s"]';

        return $packet . sprintf($format, $event, $data);
    }

    /**
     * Decode message from websocket client.
     * Define and return payload here.
     * @param Frame $frame
     * @return array
     */
    public function decode(Frame $frame)
    {
        $payload = Packet::getPayload($frame->data);

        return [
            'event' => $payload['event'] ?? null,
            'data' => $payload['data'] ?? null
        ];
    }
}

<?php

namespace Jiyis\Illuminate\Websocket;

use Swoole\Websocket\Frame;
use Swoole\Websocket\Server;
use Illuminate\Support\Facades\App;

class Parser
{
    /**
     * Strategy classes need to implement handle method.
     */
    protected $strategies = [];

    /**
     * Execute strategies before decoding payload.
     * If return value is true will skip decoding.
     * @param Server $server
     * @param Frame $frame
     * @return bool
     */
    public function execute(Server $server, Frame $frame)
    {
        $skip = false;

        foreach ($this->strategies as $strategy) {
            $result = App::call($strategy . '@handle', [
                'server' => $server,
                'frame'  => $frame
            ]);
            if ($result === true) {
                $skip = true;
                break;
            }
        }

        return $skip;
    }

    /**
     *  Encode output payload for websocket push.
     * @param $event
     * @param $data
     * @return string
     */
    public function encode($event, $data)
    {
        return json_encode([
            'event' => $event,
            'data'  => $data
        ]);
    }

    /**
     * Input message on websocket connected.
     * Define and return event name and payload data here.
     *
     * @param Frame $frame
     * @return array
     */
    public function decode(Frame $frame)
    {
        $data = json_decode($frame->data, true);

        return [
            'event' => $data['event'] ?? null,
            'data'  => $data['data'] ?? null
        ];
    }
}
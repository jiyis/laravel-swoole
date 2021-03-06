<?php

namespace Swoole\Laravel\Controllers;


use Illuminate\Http\Request;

class SocketController
{

    protected $transports = ['polling', 'websocket'];

    public function upgrade(Request $request)
    {
        if (!in_array($request->input('transport'), $this->transports)) {
            return response()->json([
                'code'    => 0,
                'message' => 'Transport unknown'
            ], 400);
        }

        if ($request->has('sid')) {
            return '1:6';
        }

        $payload = json_encode([
            'sid'          => base64_encode(uniqid()),
            'upgrades'     => ['websocket'],
            'pingInterval' => config('websocket.ping_interval'),
            'pingTimeout'  => config('websocket.ping_timeout')
        ]);

        return '97:0' . $payload . '2:40';
    }

    public function reject(Request $request)
    {
        return response()->json([
            'code'    => 3,
            'message' => 'Bad request'
        ], 400);
    }
}
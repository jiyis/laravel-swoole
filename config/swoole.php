<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HTTP server configurations.
    |--------------------------------------------------------------------------
    |
    | @see https://wiki.swoole.com/wiki/page/274.html
    |
    */
    'server' => [
        'host'               => env('SWOOLE_HOST', '127.0.0.1'),
        'port'               => env('SWOOLE_PORT', '5333'),
        'pid_file'           => env('SWOOLE_PID_FILE', base_path('storage/logs/swoole_http.pid')),
        'log_file'           => env('SWOOLE_LOG_FILE', base_path('storage/logs/swoole_http.log')),
        'log_level'          => 4,
        'daemonize'          => env('SWOOLE_DAEMONIZE', false),
        'dispatch_mode'      => 1,
        'reactor_num'        => env('SWOOLE_REACTOR_NUM', 4),
        'worker_num'         => env('SWOOLE_WORKER_NUM', 8),
        'task_worker_num'    => env('SWOOLE_TASK_WORKER_NUM', 4),
        'task_ipc_mode'      => 3,
        'task_max_request'   => 3000,
        'task_tmpdir'        => @is_writable('/dev/shm/') ? '/dev/shm' : '/tmp',
        'message_queue_key'  => ftok(base_path(), 1),
        'max_request'        => 3000,
        'open_tcp_nodelay'   => true,
        'document_root'      => base_path('public'),
        'buffer_output_size' => 16 * 1024 * 1024,
        'socket_buffer_size' => 128 * 1024 * 1024,
        'reload_async'       => true,
        'max_wait_time'      => 60,
        'enable_reuse_port'  => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel enable swoole websocket
    |--------------------------------------------------------------------------
    */

    'websocket'      => [
        'enabled' => env('ENABLE_SWOOLE_WEBSOCKET', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel inotify reload
    |--------------------------------------------------------------------------
    */
    'inotify_reload' => [
        'enable'     => env('SWOOLE_INOTIFY_RELOAD', false),
        'file_types' => ['.php'],
        'log'        => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel app sandbox
    |--------------------------------------------------------------------------
    */
    'sandbox_mode'   => env('SWOOLE_SANDBOX_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Console output will be transfered to response content if enabled.
    |--------------------------------------------------------------------------
    */
    'ob_output'      => env('SWOOLE_OB_OUTPUT', true),

    /*
    |--------------------------------------------------------------------------
    | Providers here will be registered on every request.
    |--------------------------------------------------------------------------
    */
    'providers'      => [
        '\Illuminate\Auth\AuthServiceProvider',
        '\Illuminate\Auth\Passwords\PasswordResetServiceProvider',
        '\Laravel\Passport\PassportServiceProvider',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resolved facades here will be cleared on every request.
    |--------------------------------------------------------------------------
    */
    'facades'        => [
        'auth', 'auth.driver', 'auth.password', 'request'
    ],

    /*
    |--------------------------------------------------------------------------
    | Instances here will be cleared on every request.
    |--------------------------------------------------------------------------
    */
    'instances'      => [
        'request'
    ],

    /*
    |--------------------------------------------------------------------------
    | Define your swoole tables here.
    |
    | @see https://wiki.swoole.com/wiki/page/p-table.html
    |--------------------------------------------------------------------------
    */
    'tables'         => [
        // 'table_name' => [
        //     'size' => 1024,
        //     'columns' => [
        //         ['name' => 'column_name', 'type' => Table::TYPE_STRING, 'size' => 1024],
        //     ]
        // ],
    ],
];

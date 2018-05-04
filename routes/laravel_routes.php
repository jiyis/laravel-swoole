<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register socket.io routes for your application.
|
*/

Route::group(['namespace' => 'Jiyi\Controllers'], function () {
    Route::get('socket', 'SocketController@upgrade');
    Route::post('socket', 'SocketController@reject');
});

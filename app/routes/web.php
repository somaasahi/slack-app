<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/slack', function () {
    // Slack通知
    \App\Facades\Slack::channel('test')->send('Slack連携アプリテスト');
    return 'Slack通知しました';
});

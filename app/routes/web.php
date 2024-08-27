<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/slack', function () {
    // Slack通知
    \App\Facades\Slack::channel('test')->send('テスト通知');
    return 'Slack通知しました';
});

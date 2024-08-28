<?php

use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\OAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/slack', function () {
    // Slack通知
    \App\Facades\Slack::channel('test')->send('Slack連携アプリテスト');
    return 'Slack通知しました';
});

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::prefix('oauth')->name('oauth.')->group(function () {
    Route::get('/welcome', [OAuthController::class, 'welcome'])->name('welcome');
    Route::get('/redirect', [OAuthController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [OAuthController::class, 'callback'])->name('callback');
    Route::get('/complete', [OAuthController::class, 'complete'])->name('complete');
});

Route::get('/welcome', [OAuthController::class, 'welcome'])->name('welcome');

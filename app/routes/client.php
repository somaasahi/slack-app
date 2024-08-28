<?php

use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\OAuthController;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/slack', function () {

    $client = new Client();
    $url = 'https://slack.com/api/chat.postMessage';
    $token = env('SLACK_BOT_USER_TOKEN');
    $userId = env('SLACK_USER_ID');

    try {
        $response = $client->post($url, [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json;charset=utf-8'
            ],
            'json' => [
                'channel' => $userId,
                'text' => 'Hello! これはテストメッセージです。'
            ]
        ]);

        $body = $response->getBody();
        $data = json_decode($body, true);

        if (isset($data['ok']) && $data['ok']) {
            return 'Slack通知を送信しました。';
        } else {
            return 'Slack通知の送信に失敗しました。';
        }
    } catch (GuzzleException $e) {
        return 'Slack APIへのリクエストでエラーが発生しました: ' . $e->getMessage();
    }
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

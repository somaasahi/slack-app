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


Route::get('/list', function () {
    $client = new Client();
    $token = env('SLACK_BOT_USER_TOKEN');

    // チャンネルのメンバーを取得
    try {
        $response = $client->request('GET', 'https://slack.com/api/conversations.list', [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        if (!$data['ok']) {
            return response('チャンネルの取得に失敗しました。', 500);
        }

        $channelId = null;
        foreach ($data['channels'] as $channel) {
            if ($channel['name'] === '02_develop_debug_log') {
                $channelId = $channel['id'];
                break;
            }
        }

        if (!$channelId) {
            return response('指定したチャンネルが見つかりませんでした。', 404);
        }

        $response = $client->request('GET', 'https://slack.com/api/conversations.members', [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json'
            ],
            'query' => ['channel' => $channelId]
        ]);

        $membersData = json_decode($response->getBody(), true);
        if (!$membersData['ok']) {
            return response('メンバーの取得に失敗しました。', 500);
        }

        if (empty($membersData['members'])) {
            return response('メンバーがいません。', 404);
        }

        foreach ($membersData['members'] as $memberId) {
            // メンバーごとにメッセージを送信
            try {
                $response = $client->post('https://slack.com/api/chat.postMessage', [
                    'headers' => [
                        'Authorization' => "Bearer {$token}",
                        'Content-Type' => 'application/json;charset=utf-8'
                    ],
                    'json' => [
                        'channel' => $memberId,
                        'text' => 'デバッグチャンネルにいるメンバー全員にメッセージを送信。'
                    ]
                ]);

                $postData = json_decode($response->getBody(), true);
                if (!$postData['ok']) {
                    return response('メッセージの送信に失敗しました: ' . $postData['error'], 500);
                }
            } catch (GuzzleException $e) {
                return response('メッセージ送信中にエラーが発生しました: ' . $e->getMessage(), 500);
            }
        }

        return response('全メンバーにメッセージを送信しました。', 200);
    } catch (GuzzleException $e) {
        return response('APIリクエストに失敗しました: ' . $e->getMessage(), 500);
    }
});


Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::prefix('oauth')->name('oauth.')->group(function () {
    Route::get('/welcome', [OAuthController::class, 'welcome'])->name('welcome');
    Route::get('/redirect', [OAuthController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [OAuthController::class, 'callback'])->name('callback');
    Route::get('/complete', [OAuthController::class, 'complete'])->name('complete');
});

Route::get('/welcome', [OAuthController::class, 'welcome'])->name('welcome');

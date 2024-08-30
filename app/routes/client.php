<?php

use App\Http\Controllers\Client\HomeController;
use App\Http\Controllers\Client\OAuthController;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ①ボタンを送信
Route::get('/button', function () {
    $client = new Client();
    $url = 'https://slack.com/api/chat.postMessage';
    $token = env('SLACK_BOT_USER_TOKEN');
    $id = env('SLACK_USER_ID');

    $blocks = json_encode([
        [
            "type" => "section",
            "text" => [
                "type" => "mrkdwn",
                "text" => "今日の診断を始めましょう！"
            ]
        ],
        [
            "type" => "actions",
            "elements" => [
                [
                    "type" => "button",
                    "text" => [
                        "type" => "plain_text",
                        "text" => "開始",
                        "emoji" => true
                    ],
                    "style" => "primary",
                    "value" => "start_quiz",
                    "action_id" => "report-start"
                ]
            ]
        ]
    ]);

    try {
        $response = $client->post($url, [
            'headers' => [
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json;charset=utf-8'
            ],
            'json' => [
                'channel' => $id,
                'text' => '今日の診断を始めましょう！',
                'blocks' => $blocks
            ]
        ]);

        $body = $response->getBody();
        $data = json_decode($body, true);

        if (isset($data['ok']) && $data['ok']) {
            return response()->json(['status' => 'success', 'message' => 'Button sent successfully']);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Failed to send button: ' . $data['error']]);
        }
    } catch (\GuzzleHttp\Exception\GuzzleException $e) {
        return response()->json(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
    }
});


// ②ボタン押下時にモーダルを開く
Route::post('/slack/modal', function (Request $request) {

    $data = json_decode($request->getContent(), true);

    // URL検証
    if (isset($data['type']) && $data['type'] === 'url_verification') {
        $verificationToken = config('slack.verification_token');
        if ($data['token'] === $verificationToken) {
            return response()->json(['challenge' => $data['challenge']]);
        } else {
            Log::warning('Verification token mismatch.', ['received_token' => $data['token']]);
            return response()->json(['error' => 'Verification token mismatch'], 403);
        }
    }

    // payloadはこの時点で必ずしも存在するとは限らないため、エラーハンドリングを追加
    $payload = json_decode($request->input('payload'), true);
    if (!$payload) {
        Log::error('Payload is missing or invalid');
        return response()->json(['error' => 'Payload is missing or invalid'], 400);
    }

    $trigger_id = $payload['trigger_id'];
    $blocks = [
        [
            "type" => "section",
            "text" => [
                "type" => "mrkdwn",
                "text" => "あなたの名前を選択してください。"
            ]
        ],
        [
            "type" => "actions",
            "elements" => [
                // ボタンの設定
            ]
        ],
        [
            "type" => "input",
            "element" => [
                "type" => "plain_text_input",
                "action_id" => "text_input_action"
            ],
            "label" => [
                "type" => "plain_text",
                "text" => "ヤクルトジョアは誰のものか回答してください。"
            ]
        ]
    ];

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . config('services.slack.bot_user_token'),
        'Content-Type' => 'application/json;charset=utf-8'
    ])->post('https://slack.com/api/views.open', [
        'trigger_id' => $trigger_id,
        'view' => [
            'type' => 'modal',
            'title' => [
                'type' => 'plain_text',
                'text' => 'クイズ'
            ],
            'submit' => [
                'type' => 'plain_text',
                'text' => '送信'
            ],
            'blocks' => $blocks
        ]
    ]);

    if ($response->successful()) {
        return response()->json(['status' => 'success']);
    } else {
        Log::error('Failed to open Slack modal', ['response' => $response->body()]);
        return response()->json(['error' => 'Failed to communicate with Slack API'], 500);
    }
});

// Route::get('/slack', function () {
//     $client = new Client();
//     $url = 'https://slack.com/api/chat.postMessage';
//     $token = env('SLACK_BOT_USER_TOKEN');
//     $userId = env('SLACK_USER_ID');

//     $blocks = json_encode([
//         [
//             "type" => "section",
//             "text" => [
//                 "type" => "mrkdwn",
//                 "text" => "あなたの名前を選択してください。"
//             ]
//         ],
//         [
//             "type" => "actions",
//             "elements" => [
//                 [
//                     "type" => "button",
//                     "text" => [
//                         "type" => "plain_text",
//                         "text" => "佐藤",
//                         "emoji" => true
//                     ],
//                     "value" => "option_1"
//                 ],
//                 [
//                     "type" => "button",
//                     "text" => [
//                         "type" => "plain_text",
//                         "text" => "砂糖",
//                         "emoji" => true
//                     ],
//                     "value" => "option_2"
//                 ],
//                 [
//                     "type" => "button",
//                     "text" => [
//                         "type" => "plain_text",
//                         "text" => "武藤",
//                         "emoji" => true
//                     ],
//                     "value" => "option_3"
//                 ],
//                 [
//                     "type" => "button",
//                     "text" => [
//                         "type" => "plain_text",
//                         "text" => "無糖",
//                         "emoji" => true
//                     ],
//                     "value" => "option_4"
//                 ]
//             ]
//         ],
//         [
//             "type" => "input",
//             "element" => [
//                 "type" => "plain_text_input",
//                 "action_id" => "text_input_action",
//                 "placeholder" => [
//                     "type" => "plain_text",
//                     "text" => "本居宣長"
//                 ]
//             ],
//             "label" => [
//                 "type" => "plain_text",
//                 "text" => "ヤクルトジョアは誰のものか回答してください。",
//                 "emoji" => true
//             ]
//         ]
//     ]);

//     try {
//         $response = $client->post($url, [
//             'headers' => [
//                 'Authorization' => "Bearer {$token}",
//                 'Content-Type' => 'application/json;charset=utf-8'
//             ],
//             'json' => [
//                 'channel' => $userId,
//                 'text' => 'インタラクティブなメッセージ',
//                 'blocks' => $blocks
//             ]
//         ]);

//         $body = $response->getBody();
//         $data = json_decode($body, true);

//         if (isset($data['ok']) && $data['ok']) {
//             return 'Slack通知を送信しました。';
//         } else {
//             return 'Slack通知の送信に失敗しました。エラー: ' . $data['error'];
//         }
//     } catch (GuzzleException $e) {
//         return 'Slack APIへのリクエストでエラーが発生しました: ' . $e->getMessage();
//     }
// });

// Route::get('/list', function () {
//     $client = new Client();
//     $token = env('SLACK_BOT_USER_TOKEN');

//     // チャンネルのメンバーを取得
//     try {
//         $response = $client->request('GET', 'https://slack.com/api/conversations.list', [
//             'headers' => [
//                 'Authorization' => "Bearer {$token}",
//                 'Content-Type' => 'application/json'
//             ]
//         ]);

//         $data = json_decode($response->getBody(), true);
//         if (!$data['ok']) {
//             return response('チャンネルの取得に失敗しました。', 500);
//         }

//         $channelId = null;
//         foreach ($data['channels'] as $channel) {
//             if ($channel['name'] === '02_develop_debug_log') {
//                 $channelId = $channel['id'];
//                 break;
//             }
//         }

//         if (!$channelId) {
//             return response('指定したチャンネルが見つかりませんでした。', 404);
//         }

//         $response = $client->request('GET', 'https://slack.com/api/conversations.members', [
//             'headers' => [
//                 'Authorization' => "Bearer {$token}",
//                 'Content-Type' => 'application/json'
//             ],
//             'query' => ['channel' => $channelId]
//         ]);

//         $membersData = json_decode($response->getBody(), true);
//         if (!$membersData['ok']) {
//             return response('メンバーの取得に失敗しました。', 500);
//         }

//         if (empty($membersData['members'])) {
//             return response('メンバーがいません。', 404);
//         }

//         foreach ($membersData['members'] as $memberId) {
//             // メンバーごとにメッセージを送信
//             try {
//                 $response = $client->post('https://slack.com/api/chat.postMessage', [
//                     'headers' => [
//                         'Authorization' => "Bearer {$token}",
//                         'Content-Type' => 'application/json;charset=utf-8'
//                     ],
//                     'json' => [
//                         'channel' => $memberId,
//                         'text' => 'デバッグチャンネルにいるメンバー全員にメッセージを送信。'
//                     ]
//                 ]);

//                 $postData = json_decode($response->getBody(), true);
//                 if (!$postData['ok']) {
//                     return response('メッセージの送信に失敗しました: ' . $postData['error'], 500);
//                 }
//             } catch (GuzzleException $e) {
//                 return response('メッセージ送信中にエラーが発生しました: ' . $e->getMessage(), 500);
//             }
//         }

//         return response('全メンバーにメッセージを送信しました。', 200);
//     } catch (GuzzleException $e) {
//         return response('APIリクエストに失敗しました: ' . $e->getMessage(), 500);
//     }
// });


Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::prefix('oauth')->name('oauth.')->group(function () {
    Route::get('/welcome', [OAuthController::class, 'welcome'])->name('welcome');
    Route::get('/redirect', [OAuthController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [OAuthController::class, 'callback'])->name('callback');
    Route::get('/complete', [OAuthController::class, 'complete'])->name('complete');
});

Route::get('/welcome', [OAuthController::class, 'welcome'])->name('welcome');

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
                "text" => "今日の自己診断を始めましょう。"
            ]
        ],
        [
            "type" => "actions",
            "elements" => [
                [
                    "type" => "button",
                    "text" => [
                        "type" => "plain_text",
                        "text" => "始める",
                        "emoji" => true
                    ],
                    "style" => "primary",
                    "value" => "report-start",
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
                'text' => '今日の調子はどうですか',
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

    // 受信をSlackにサーバーに応答
    response()->json(['status' => 'received'])->send();

    $data = json_decode($request->getContent(), true);
    // URL検証
    if (isset($data['type']) && $data['type'] === 'url_verification') {
        $verificationToken = config('slack.oauth.verification_token');
        if ($data['token'] === $verificationToken) {
            return response()->json(['challenge' => $data['challenge']]);
        } else {
            Log::warning('Verification token mismatch.', ['received_token' => $data['token']]);
            return response()->json(['error' => 'Verification token mismatch'], 403);
        }
    }

    $payload = json_decode($request->input('payload'), true);
    if (!$payload) {
        Log::error('Payload is missing or invalid');
        return response()->json(['error' => 'Payload is missing or invalid'], 400);
    }

    // モーダルを開く
    if ($payload['type'] === 'block_actions') {

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('SLACK_BOT_USER_TOKEN'),
            'Content-Type' => 'application/json',
        ])->post('https://slack.com/api/views.open', [
            'trigger_id' => $payload['trigger_id'],
            'view' => [
                'type' => 'modal',
                'callback_id' => 'submit_report',
                'title' => [
                    'type' => 'plain_text',
                    'text' => date('Y/m/d'),
                    'emoji' => true,
                ],
                'submit' => [
                    'type' => 'plain_text',
                    'text' => '記録',
                    'emoji' => true,
                ],
                'close' => [
                    'type' => 'plain_text',
                    'text' => '閉じる',
                    'emoji' => true,
                ],
                'blocks' => [
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "mrkdwn",
                            "text" => "今日の心の状態を教えてください。"
                        ]
                    ],
                    [
                        "type" => "actions",
                        "elements" => [
                            [
                                "type" => "radio_buttons",
                                "options" => [
                                    [
                                        "text" => [
                                            "type" => "plain_text",
                                            "text" => "とても良い",
                                            "emoji" => true
                                        ],
                                        "value" => "option_1"
                                    ],
                                    [
                                        "text" => [
                                            "type" => "plain_text",
                                            "text" => "良い",
                                            "emoji" => true
                                        ],
                                        "value" => "option_2"
                                    ],
                                    [
                                        "text" => [
                                            "type" => "plain_text",
                                            "text" => "いつも通り",
                                            "emoji" => true
                                        ],
                                        "value" => "option_3"
                                    ],
                                    [
                                        "text" => [
                                            "type" => "plain_text",
                                            "text" => "もやもやしている",
                                            "emoji" => true
                                        ],
                                        "value" => "option_4"
                                    ],
                                    [
                                        "text" => [
                                            "type" => "plain_text",
                                            "text" => "嫌な気分",
                                            "emoji" => true
                                        ],
                                        "value" => "option_5"
                                    ]
                                ],
                            ]
                        ]
                    ],
                    [
                        'block_id' => 'block_id_text',
                        'type' => 'input',
                        'element' => [
                            'type' => 'plain_text_input',
                            'placeholder' => [
                                'type' => 'plain_text',
                                'text' => '記入内容は他のユーザーから閲覧されません',
                            ],
                        ],
                        'label' => [
                            'type' => 'plain_text',
                            'text' => '不安やストレスを文字にすると、少し楽になることがあります。',
                            'emoji' => true,
                        ],
                    ],
                ],
            ],
        ]);
    }

    // モーダルの送信ボタンを押下したとき
    if ($payload['type'] === 'view_submission') {
        $viewId = $payload['view']['root_view_id'];
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('SLACK_BOT_USER_TOKEN'),
            'Content-Type' => 'application/json',
        ])->post('https://slack.com/api/views.update',
        // モーダルの内容を「記録しました」というコメントと、モーダルを閉じるボタンに変更
        [
            'view_id' => $viewId,
            'view' => [
                'type' => 'modal',
                'title' => [
                    'type' => 'plain_text',
                    'text' => date('Y/m/d'),
                    'emoji' => true,
                ],
                'close' => [
                    'type' => 'plain_text',
                    'text' => '閉じる',
                    'emoji' => true,
                ],
                'blocks' => [
                    [
                        "type" => "section",
                        "text" => [
                            "type" => "mrkdwn",
                            "text" => "記録しました。"
                        ]
                    ]
                ],
            ],
        ]);
    }

    if ($response->successful()) {
        return response()->json(['status' => 'success']);
    } else {
        return response()->json(['status' => 'error', 'message' => $response->body()]);
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

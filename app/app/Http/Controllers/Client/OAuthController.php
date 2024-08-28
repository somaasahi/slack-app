<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    public function welcome()
    {
        return view('client.oauth.welcome');
    }

    public function redirect()
    {
        $state = Str::random(40);
        session(['oauth_state' => $state]);

        $query = http_build_query([
            'scope' => 'identity.basic',
            'client_id' => config('slack.oauth.client_id'),
            'redirect_uri' => config('slack.oauth.redirect_uri'),
            'state' => $state,
            'team' => config('slack.team'),
        ]);

        return redirect(config('slack.oauth.authorize_url') . '?' . $query);
    }

    public function callback(Request $request)
    {
         // TODO: 例外共通クラスを使ってエラー画面+ログ出力

        if ($request->input('state') !== session('oauth_state')) {
            return response('Something Wrong!', 500);
        }

        if ($request->filled('error')) {
            return response('slack returned error.',500);
        }

        $guzzile = new \GuzzleHttp\Client();

        $params = [
            'code' => $request->input('code'),
            'client_id' => config('slack.oauth.client_id'),
            'client_secret' => config('slack.oauth.client_secret'),
        ];

        $option = [
            'form_params' => $params,
        ];

        $response = $guzzile->post(config('slack.oauth.oauth_url'), $option);
        $body = $response->getBody();

        $data = json_decode( (String)$body, true);

        if(!$data['ok']){
            return response('OAuth request returns error!', 500);
        }

        // TODO: 暗号化しDBに保存
        $token = $data['access_token'];

        return redirect()->route('oauth.complete');
    }

    public function complete()
    {
        return view('client.oauth.complete');
    }
}


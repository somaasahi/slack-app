<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OAuthController extends Controller
{
    const SLACK_OAUTH_URL = 'https://slack.com/api/oauth.access';

    public function index(Request $request ){
        return view('oauth.index');
    }

    //Slackのredirect_urlからコールバックされるメソッド
    public function auth(Request $request){
        \Log::info($request);
        $code = $request->input('code');
        $state = $request->input('state');

        if($state != 'slalalala'){
            return response('Something Wrong!', 500);
        }
        if($request->filled('error')){
            return response('slack returned error.',500);
        }

        $guzzile = new \GuzzleHttp\Client();

        $params = [];
        $params['code'] = $code;
        $params['client_id'] = config('slack.oauth.client_id');
        $params['client_secret'] = config('slack.oauth.client_secret');

        $option = [];
        $option['form_params']=$params;

        $response = $guzzile->post(self::SLACK_OAUTH_URL, $option);
        $body = $response->getBody();
        \Log::info($body);

        $data = json_decode( (String)$body, true);
        if(!$data['ok']){
            return response('OAuth request returns error!', 500);
        }
        $token = $data['access_token'];
        $userName = $data['user']['name'];
        $userId = $data['user']['id'];
        $teamId = $data['team']['id'];
        \Log::info([$token, $userName, $userId, $teamId]);

        var_dump((String)$body);

        //この先でトークン情報をDBなどに保存しておくこと
        //失うともう一度OAuthする必要がでてくる

        //最後にLaravelのルールでViewの情報を返す。（今回はサンプルなのでOKだけ戻す）
        return "ok";
    }
}


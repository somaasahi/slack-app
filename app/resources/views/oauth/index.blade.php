<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>oauth Bot OAuth</title>
    </head>
    <body>
        <div class="flex-center position-ref full-height">

            <div class="content">
                <div class="title m-b-md">
                    oauth-bot OAuth
                </div>
                <form action="https://slack.com/oauth/authorize" method="GET">
                    <input type="hidden" name="scope" value="identity.basic">
                    <input type="hidden" name="client_id" value="{{config('slack.oauth.client_id')}}">
                    <input type="hidden" name="redirect_uri" value="{{config('slack.oauth.redirect_url')}}">

                    <input type="hidden" name="state" value="slalalala">
                    <input type="hidden" name="team" value="{{config('slack.team')}}">
                    <input type="submit" value="認証ページへ">
                </form>
            </div>
        </div>
    </body>
</html>


<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body>
        <div class="flex-center position-ref full-height">

            <div class="content">
                <div class="title m-b-md">
                    ボタンを押下してSLACKとの連携を始めましょう
                </div>
                <a href="{{ route('oauth.redirect') }}">認証ページへ</a>
            </div>
        </div>
    </body>
</html>


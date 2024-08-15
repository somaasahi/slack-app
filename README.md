## 概要

slack-app

## 環境構築

Notion を参考

## 環境構築コマンド

### コンテナ作成 & コマンド起動

- コンテナ構築と立ち上げ
  `docker compose up -d --build`

- 立ち上げコマンド
  `docker compose up -d`

### 既存コンテナでコマンド実行

- Appコンテナに対してcomposerをインストールする
  `docker compose exec app composer install`

- Appコンテナに対して、phpコマンドでapp keyの生成を行う
  `docker compose exec app php artisan key:generate`

- npmのインストールを行う
  `docker compose exec app npm install`

- npmを実行する
  `docker compose exec app npm run dev`
<?php

namespace App\Notifications;

use App\Services\SlackService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class SlackNotification extends Notification
{
    use Queueable;

    /**
     * @var array 通知チャンネル情報
     */
    private array $channel;

    /**
     * @var string 通知メッセージ
     */
    private string $message;

    /**
     * @var array|null 添付情報
     */
    private ?array $attachment;

    /**
     * 通知インスタンスの作成
     *
     * @return void
     */
    public function __construct(array $channel, string $message, ?array $attachment = null)
    {
        $this->channel = $channel;
        $this->message = '[' . config("app.env") . ']: ' . $message;
        $this->attachment = $attachment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['slack'];
    }

    public function toSlack(SlackService $notifiable)
    {
        // NOTE: ->to()の引数にはslackユーザIDを指定すると、個別に通知が可能
        $to = "U04PQ7R78DQ";

        // $to = $this->channel['channel'];

        $message = (new SlackMessage())
            ->from($this->channel['username'], $this->channel['icon'])
            ->to($to)
            ->content($this->message)
            ->attachment(function ($attachment) {
                $attachment->title('質問内容はこちらです。')
                    ->content('以下のオプションから選んでください')
                    ->callbackId('response_action')  // Slack APIでのアクション識別用
                    ->action('選択肢1', 'http://www.hogehoge.example', 'default')
                    ->action('選択肢2', 'http://www.hogehoge.example', 'default')
                    ->action('選択肢3', 'http://www.hogehoge.example', 'default')
                    ->action('選択肢4', 'http://www.hogehoge.example', 'default');
            });

        // if (!is_null($this->attachment) && is_array($this->attachment)) {
        //     $message->attachment(function ($attachment) {
        //         if (isset($this->attachment['title'])) {
        //             $attachment->title($this->attachment['title']);
        //         }
        //         if (isset($this->attachment['content'])) {
        //             $attachment->content($this->attachment['content']);
        //         }
        //         if (isset($this->attachment['field']) && is_array($this->attachment['field'])) {
        //             foreach ($this->attachment['field'] as $k => $v) {
        //                 $attachment->field($k, $v);
        //             }
        //         }
        //     });
        // }
        return $message;
    }
}

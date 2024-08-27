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
        $message = (new SlackMessage())
            ->from($this->channel['username'], $this->channel['icon'])
            ->to($this->channel['channel'])
            ->content($this->message);

        if (!is_null($this->attachment) && is_array($this->attachment)) {
            $message->attachment(function ($attachment) {
                if (isset($this->attachment['title'])) {
                    $attachment->title($this->attachment['title']);
                }
                if (isset($this->attachment['content'])) {
                    $attachment->content($this->attachment['content']);
                }
                if (isset($this->attachment['field']) && is_array($this->attachment['field'])) {
                    foreach ($this->attachment['field'] as $k => $v) {
                        $attachment->field($k, $v);
                    }
                }
            });
        }
        return $message;
    }
}

<?php

namespace App\Services;

use App\Notifications\SlackNotification;
use Illuminate\Notifications\Notifiable;

class SlackService
{
    use Notifiable;

    /**
     * 通知チャンネル情報
     *
     * @var array|null
     */
    private ?array $channel = null;

    public function send(string $message): void
    {
        if (!isset($this->channel)) {
            $this->channel(config("slack.default"));
        }

        $this->notify(new SlackNotification($this->channel, $message));
    }

    /**
     * 通知チャンネルを指定
     *
     * @param string $channel
     * @return $this
     */
    public function channel(string $channel): SlackService
    {
        $this->channel = config("slack.channels." . $channel);
        return $this;
    }

    /**
     * Slack通知用URLを指定する
     *
     * @return string
     */
    protected function routeNotificationForSlack(): string
    {
        return config('slack.url');
    }
}

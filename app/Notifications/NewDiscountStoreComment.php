<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\DiscountStore;
use App\Models\DiscountStoreComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordChannel;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordEmbed;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordMessage;

final class NewDiscountStoreComment extends Notification
{
    use Queueable;

    public function __construct(
        private DiscountStore $store,
        private DiscountStoreComment $comment,
    ) {}

    public function via(object $notifiable): array
    {
        return [
            DiscordChannel::class,
        ];
    }

    public function toDiscordWebhook(object $notifiable): DiscordMessage
    {
        return DiscordMessage::create()
            ->embed(
                DiscordEmbed::make(
                    title: '新留言待審核',
                    description: '**'.$this->store->name.'** 收到新的留言，請前往管理後台審核。',
                    url: url("/admin/discount-stores/{$this->store->id}/edit"),
                )->with([
                    'color' => '16426522',
                    'fields' => [
                        [
                            'name' => '店家名稱',
                            'value' => $this->store->name,
                            'inline' => true,
                        ],
                        [
                            'name' => '留言暱稱',
                            'value' => $this->comment->nickname,
                            'inline' => true,
                        ],
                        [
                            'name' => '留言內容',
                            'value' => $this->comment->content,
                            'inline' => false,
                        ],
                    ],
                ]),
            )->with([
                'username' => 'NOU Tools',
                'avatar_url' => 'https://nou-tools.binota.org/favicon.png',
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'store_id' => $this->store->id,
            'comment_id' => $this->comment->id,
            'nickname' => $this->comment->nickname,
        ];
    }
}

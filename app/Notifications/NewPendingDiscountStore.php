<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\DiscountStore;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordChannel;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordEmbed;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordMessage;

final class NewPendingDiscountStore extends Notification
{
    use Queueable;

    public function __construct(
        private DiscountStore $store,
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
                    title: '新店家待審核',
                    description: $this->store->name.' 等待審核中',
                    url: url("/admin/discount-stores/{$this->store->id}/edit"),
                )->with([
                    'color' => '16426522',
                    'fields' => [
                        [
                            'name' => '店家名稱',
                            'value' => $this->store->name ?? '',
                            'inline' => true,
                        ],
                        [
                            'name' => '店家類型',
                            'value' => $this->store->type?->label() ?? '未知',
                            'inline' => true,
                        ],
                        [
                            'name' => '地區',
                            'value' => $this->store->city.$this->store->district,
                            'inline' => true,
                        ],
                        [
                            'name' => '地址',
                            'value' => $this->store->address ?? '-# 未填寫',
                            'inline' => false,
                        ],
                        [
                            'name' => '驗證方法',
                            'value' => $this->store->verification_method ?: '-# 未填寫',
                            'inline' => false,
                        ],
                        [
                            'name' => '優惠內容',
                            'value' => $this->store->discount_details ?: '-# 未填寫',
                            'inline' => false,
                        ],
                        [
                            'name' => '備註',
                            'value' => $this->store->notes ?: '-# 未填寫',
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
            //
        ];
    }
}

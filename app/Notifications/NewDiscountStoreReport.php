<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\DiscountStore;
use App\Models\DiscountStoreReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordChannel;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordEmbed;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordMessage;

final class NewDiscountStoreReport extends Notification
{
    use Queueable;

    public function __construct(
        private DiscountStore $store,
        private DiscountStoreReport $report,
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
                    title: '新店家回報',
                    description: $this->store->name.' 被回報**'.($this->report->is_valid ? '有效' : '無效').'**',
                    url: url("/admin/discount-stores/{$this->store->id}/edit"),
                )->with([
                    'color' => $this->report->is_valid ? '4437377' : '15746887',
                    'fields' => [
                        [
                            'name' => '店家名稱',
                            'value' => $this->store->name,
                            'inline' => true,
                        ],
                        [
                            'name' => '回報內容',
                            'value' => $this->report->comment ?? '-# 無',
                            'inline' => false,
                        ],
                    ],
                ]),
            )->with([
                'username' => 'NOU Tools',
                'avatar_url' => url('https://nou-tools.binota.org/favicon.png'),
            ]);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
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

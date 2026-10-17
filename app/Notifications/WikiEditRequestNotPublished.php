<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\WikiEditRequests\WikiEditRequestResource;
use App\Models\WikiEditEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordChannel;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordEmbed;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordMessage;

/**
 * An edit that did not go through: the editor declined or failed, the page
 * moved on, or a reviewer rejected it.
 */
final class WikiEditRequestNotPublished extends Notification
{
    use Queueable;

    public function __construct(
        private WikiEditEvent $event,
    ) {}

    public function via(object $notifiable): array
    {
        return [
            DiscordChannel::class,
        ];
    }

    public function toDiscordWebhook(object $notifiable): DiscordMessage
    {
        $target = $this->event->page_title ?? $this->event->page_slug ?? '（新條目）';

        $embed = DiscordEmbed::make(
            title: '知識庫修改未通過：'.$this->event->event->label(),
            description: '**'.$target.'**',
            url: $this->event->wiki_edit_request_id === null
                ? null
                : WikiEditRequestResource::getUrl('view', ['record' => $this->event->wiki_edit_request_id]),
        )->with([
            'color' => '15158332',
            'fields' => [
                [
                    'name' => '修改說明',
                    'value' => Str::limit((string) ($this->event->instruction ?: '—'), 1000),
                    'inline' => false,
                ],
                [
                    'name' => '原因',
                    'value' => Str::limit((string) ($this->event->detail ?: '—'), 1000),
                    'inline' => false,
                ],
            ],
        ]);

        return DiscordMessage::create()
            ->embed($embed)
            ->with([
                'username' => 'NOU Tools',
                'avatar_url' => 'https://nou-tools.binota.org/favicon.png',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'wiki_edit_event_id' => $this->event->id,
        ];
    }
}

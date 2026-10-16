<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WikiEditRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordChannel;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordEmbed;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordMessage;

final class WikiEditRequestNeedsReview extends Notification
{
    use Queueable;

    public function __construct(
        private WikiEditRequest $editRequest,
    ) {}

    public function via(object $notifiable): array
    {
        return [
            DiscordChannel::class,
        ];
    }

    public function toDiscordWebhook(object $notifiable): DiscordMessage
    {
        $reasons = $this->editRequest->ai_verdict['reviewReasons'] ?? [];

        return DiscordMessage::create()
            ->embed(
                DiscordEmbed::make(
                    title: $this->editRequest->isNewPage() ? '知識庫新條目待審核' : '知識庫修改待審核',
                    description: '**'.$this->editRequest->draft_title.'**：'.$this->editRequest->draft_edit_summary,
                    url: url("/admin/wiki-edit-requests/{$this->editRequest->id}"),
                )->with([
                    'color' => '16426522',
                    'fields' => [
                        [
                            'name' => '修改說明',
                            'value' => Str::limit($this->editRequest->instruction, 1000),
                            'inline' => false,
                        ],
                        [
                            'name' => '需要審核的原因',
                            'value' => $reasons === [] ? '—' : implode('、', $reasons),
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
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'wiki_edit_request_id' => $this->editRequest->id,
        ];
    }
}

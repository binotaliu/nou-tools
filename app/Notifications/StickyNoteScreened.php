<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\StickyNoteStatus;
use App\Filament\Resources\CafeStickyNotes\CafeStickyNoteResource;
use App\Models\CafeStickyNote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordChannel;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordEmbed;
use Revolution\Laravel\Notification\DiscordWebhook\DiscordMessage;

/**
 * A new sticky note after its pre-screen: either already on the wall or held
 * for an admin.
 */
final class StickyNoteScreened extends Notification
{
    use Queueable;

    public function __construct(private CafeStickyNote $note) {}

    public function via(object $notifiable): array
    {
        return [DiscordChannel::class];
    }

    public function toDiscordWebhook(object $notifiable): DiscordMessage
    {
        $needsReview = $this->note->status === StickyNoteStatus::PendingReview;
        $nickname = $this->note->schedule?->studyRoomProfile?->nickname;

        $embed = DiscordEmbed::make(
            title: $needsReview ? '新便利貼待審核' : '新便利貼已張貼',
            description: Str::limit($this->note->text ?: '（僅圖畫）', 1000),
            url: CafeStickyNoteResource::getUrl('index'),
        )->with([
            'color' => $needsReview ? '16426522' : '3066993',
            'fields' => [
                ['name' => '暱稱', 'value' => $nickname ?? '—', 'inline' => true],
                ['name' => '附圖', 'value' => $this->note->drawing_path === null ? '無' : '有', 'inline' => true],
                [
                    'name' => 'AI 判斷',
                    'value' => Str::limit((string) ($this->note->ai_verdict['reason'] ?? '—') ?: '—', 1000),
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
        return ['cafe_sticky_note_id' => $this->note->id];
    }
}

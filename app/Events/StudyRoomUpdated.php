<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\StudyRoomSeat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomSeatViewModel;
use NouTools\Domains\StudyRoom\ViewModels\StudyRoomTotalsViewModel;

/**
 * Broadcasts a single 自習室 change (a seat taken/left, a timer started or
 * stopped, an idle release, ...) to every connected browser.
 *
 * Uses `ShouldBroadcastNow` rather than `ShouldBroadcast` on purpose: this
 * app is deployed by a separate repo we don't control, so a queue worker
 * being alive can't be guaranteed. The payload here is tiny and only ever
 * fires at human interaction frequency (someone clicking a seat, a timer
 * finishing), so making the Reverb call synchronously, inline with the
 * request, is an acceptable trade for not silently losing broadcasts to an
 * un-worked queue.
 */
final class StudyRoomUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $type,
        public ?StudyRoomSeat $seat,
        public int $openFloors,
        public StudyRoomTotalsViewModel $totals,
        public string $version,
    ) {}

    /**
     * A public channel: the room is public and there are no accounts, so
     * there's nothing to authorize — no channel auth, no routes/channels.php.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('study-room');
    }

    public function broadcastAs(): string
    {
        return 'study-room.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'seatCode' => $this->seat?->code,
            // Built with a null viewer: this fans out to everyone, so
            // `isYou` can never be viewer-relative here. The client decides
            // `isYou` itself from the seat code it already holds.
            'seat' => $this->seat === null ? null : StudyRoomSeatViewModel::fromModel($this->seat, null),
            'openFloors' => $this->openFloors,
            'totals' => $this->totals,
            'version' => $this->version,
        ];
    }
}

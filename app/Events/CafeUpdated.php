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

/**
 * One café change (a seat taken or left, later a coffee ordered or a sticky
 * note posted) for every connected browser. Like `StudyRoomUpdated` it is
 * `ShouldBroadcastNow`: no queue worker is guaranteed, and the volume is
 * human-click sized.
 */
final class CafeUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $type,
        public ?StudyRoomSeat $seat,
        public int $occupantCount,
        public string $version,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('cafe');
    }

    public function broadcastAs(): string
    {
        return 'cafe.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'seatCode' => $this->seat?->code,
            // Built without a viewer: the client derives `isYou` itself.
            'seat' => $this->seat === null ? null : StudyRoomSeatViewModel::fromModel($this->seat, null),
            'occupantCount' => $this->occupantCount,
            'version' => $this->version,
        ];
    }
}

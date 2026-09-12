<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Events\StudyRoomUpdated;
use App\Models\StudyRoomSeat;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fires `StudyRoomUpdated` for the given change, swallowing any failure.
 *
 * `StudyRoomUpdated` implements `ShouldBroadcastNow`, so `event()` here
 * makes a synchronous HTTP call to Reverb as part of handling the request.
 * If Reverb is down or unreachable, that call throws — and without this
 * try/catch, a student clicking a seat or starting a timer would get a 500
 * whenever the broadcast server happens to be unavailable. Catching and
 * logging instead means the write itself still succeeds and the feature
 * silently degrades to the client's polling fallback.
 */
final readonly class BroadcastStudyRoomChange
{
    public function __construct(
        private BuildStudyRoomState $buildStudyRoomState,
    ) {}

    public function __invoke(string $type, ?StudyRoomSeat $seat = null): void
    {
        try {
            // Built with a null viewer purely to derive the current
            // openFloors/totals/version for the broadcast payload — the
            // per-seat `isYou` flag is handled separately by
            // `StudyRoomUpdated::broadcastWith()`.
            $state = ($this->buildStudyRoomState)(null);

            event(new StudyRoomUpdated(
                type: $type,
                seat: $seat,
                openFloors: $state->openFloors,
                totals: $state->totals,
                version: $state->version,
            ));
        } catch (Throwable $throwable) {
            Log::warning('自習室即時廣播失敗，已略過。', [
                'type' => $type,
                'exception' => $throwable,
            ]);
        }
    }
}

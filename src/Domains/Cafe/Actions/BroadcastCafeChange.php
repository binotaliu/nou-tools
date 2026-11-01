<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Events\CafeUpdated;
use App\Models\CafeSeat;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fires `CafeUpdated`, swallowing any failure: a Reverb outage must never turn
 * a seat claim into a 500.
 */
final readonly class BroadcastCafeChange
{
    public function __construct(private BuildCafeState $buildCafeState) {}

    public function __invoke(string $type, ?CafeSeat $seat = null): void
    {
        try {
            $state = ($this->buildCafeState)(null);

            event(new CafeUpdated(
                type: $type,
                seat: $seat,
                occupantCount: $state->occupantCount,
                version: $state->version,
            ));
        } catch (Throwable $throwable) {
            Log::warning('咖啡廳即時廣播失敗，已略過。', [
                'type' => $type,
                'exception' => $throwable,
            ]);
        }
    }
}

<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use Illuminate\Http\Request;
use NouTools\Domains\Cafe\ViewModels\BaristaLineViewModel;

/**
 * One turn of barista small talk. The ids shown lately live in the session so
 * the same line is not repeated back to back.
 */
final readonly class TalkToBarista
{
    private const REMEMBERED_LINES = 6;

    public function __construct(
        private BuildBaristaContext $buildBaristaContext,
        private PickBaristaLine $pickBaristaLine,
    ) {}

    public function __invoke(Request $request, ?string $drink = null): ?BaristaLineViewModel
    {
        $afterOrder = $drink !== null;
        $recent = (array) $request->session()->get('cafe.barista.recent', []);

        $line = ($this->pickBaristaLine)(
            ($this->buildBaristaContext)($request->studentScheduleFromCookie(), $drink),
            $afterOrder,
            array_map('intval', $recent),
        );

        if ($line !== null) {
            $request->session()->put(
                'cafe.barista.recent',
                array_slice([...$recent, $line->id], -self::REMEMBERED_LINES),
            );
        }

        return $line;
    }
}

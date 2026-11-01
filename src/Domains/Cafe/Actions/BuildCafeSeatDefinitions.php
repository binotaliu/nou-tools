<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\CafeSeatKind;

/**
 * Derives the canonical café layout from `config('cafe.layout')`. Pure
 * function; `SyncCafeSeats` reconciles the database against it.
 */
final readonly class BuildCafeSeatDefinitions
{
    /**
     * @return array<int, array{kind: CafeSeatKind, group_code: ?string, seat_number: int, code: string, label: string}>
     */
    public function __invoke(): array
    {
        $definitions = [];

        for ($number = 1; $number <= (int) config('cafe.layout.bar_stools'); $number++) {
            $definitions[] = [
                'kind' => CafeSeatKind::Bar,
                'group_code' => null,
                'seat_number' => $number,
                'code' => sprintf('B%02d', $number),
                'label' => sprintf('吧台 %02d 號', $number),
            ];
        }

        $tables = (int) config('cafe.layout.window_tables');
        $perTable = (int) config('cafe.layout.seats_per_window_table');

        for ($table = 1; $table <= $tables; $table++) {
            for ($number = 1; $number <= $perTable; $number++) {
                $definitions[] = [
                    'kind' => CafeSeatKind::Window,
                    'group_code' => 'W'.$table,
                    'seat_number' => $number,
                    'code' => sprintf('W%d-%d', $table, $number),
                    'label' => sprintf('靠牆 %d 號桌 %d 位', $table, $number),
                ];
            }
        }

        for ($number = 1; $number <= (int) config('cafe.layout.communal_seats'); $number++) {
            $definitions[] = [
                'kind' => CafeSeatKind::Communal,
                'group_code' => 'C',
                'seat_number' => $number,
                'code' => sprintf('C%02d', $number),
                'label' => sprintf('大長桌 %d 位', $number),
            ];
        }

        return $definitions;
    }
}

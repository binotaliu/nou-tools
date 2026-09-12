<?php

declare(strict_types=1);

namespace NouTools\Domains\StudyRoom\Actions;

use App\Enums\StudySeatKind;

/**
 * Derives the canonical seat layout from `config('study-room.layout')` and
 * `config('study-room.floors.max')`. Pure function, no DB access — the
 * result is what `SyncStudyRoomSeats` reconciles the database against.
 */
final readonly class BuildSeatDefinitions
{
    /**
     * @return array<int, array{floor: int, kind: StudySeatKind, group_code: ?string, seat_number: int, code: string, label: string}>
     */
    public function __invoke(): array
    {
        $soloSeatsPerFloor = (int) config('study-room.layout.solo_seats_per_floor');
        $tablesPerFloor = (int) config('study-room.layout.tables_per_floor');
        $seatsPerTable = (int) config('study-room.layout.seats_per_table');
        $maxFloor = (int) config('study-room.floors.max');

        $definitions = [];

        for ($floor = 1; $floor <= $maxFloor; $floor++) {
            $definitions = [
                ...$definitions,
                ...$this->buildSoloSeats($floor, $soloSeatsPerFloor),
                ...$this->buildTableSeats($floor, $tablesPerFloor, $seatsPerTable),
            ];
        }

        return $definitions;
    }

    /**
     * @return array<int, array{floor: int, kind: StudySeatKind, group_code: ?string, seat_number: int, code: string, label: string}>
     */
    private function buildSoloSeats(int $floor, int $soloSeatsPerFloor): array
    {
        $seats = [];

        for ($seatNumber = 1; $seatNumber <= $soloSeatsPerFloor; $seatNumber++) {
            $seats[] = [
                'floor' => $floor,
                'kind' => StudySeatKind::Solo,
                'group_code' => null,
                'seat_number' => $seatNumber,
                'code' => sprintf('%d-S%02d', $floor, $seatNumber),
                'label' => sprintf('%dF 單人座 %02d', $floor, $seatNumber),
            ];
        }

        return $seats;
    }

    /**
     * @return array<int, array{floor: int, kind: StudySeatKind, group_code: ?string, seat_number: int, code: string, label: string}>
     */
    private function buildTableSeats(int $floor, int $tablesPerFloor, int $seatsPerTable): array
    {
        $seats = [];

        for ($tableNumber = 1; $tableNumber <= $tablesPerFloor; $tableNumber++) {
            for ($seatNumber = 1; $seatNumber <= $seatsPerTable; $seatNumber++) {
                $seats[] = [
                    'floor' => $floor,
                    'kind' => StudySeatKind::Shared,
                    'group_code' => 'T'.$tableNumber,
                    'seat_number' => $seatNumber,
                    'code' => sprintf('%d-T%d-%d', $floor, $tableNumber, $seatNumber),
                    'label' => sprintf('%dF %d 號桌 %d 位', $floor, $tableNumber, $seatNumber),
                ];
            }
        }

        return $seats;
    }
}

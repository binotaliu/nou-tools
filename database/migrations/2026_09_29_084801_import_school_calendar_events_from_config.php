<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Snapshot of the former config/school-schedules.php: [term, start, end, name, countdown].
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: bool}>
     */
    private const EVENTS = [
        ['2025B', '2026-02-01', '2026-02-01', '114下學期開始', false],
        ['2025B', '2026-02-23', '2026-02-23', '114下學期課程開播', true],
        ['2025B', '2026-03-12', '2026-03-14', '114下學期畢業申請', false],
        ['2025B', '2026-04-25', '2026-04-26', '114下學期期中考', true],
        ['2025B', '2026-05-01', '2026-05-20', '115暑期選課', false],
        ['2025B', '2026-05-02', '2026-05-03', '114下學期期中考補考', false],
        ['2025B', '2026-05-13', '2026-05-13', '114下學期期中考成績開放網路查詢', false],
        ['2025B', '2026-05-23', '2026-05-31', '115暑期線上補選課', false],
        ['2025B', '2026-06-27', '2026-06-28', '114下學期期末考', true],
        ['2025B', '2026-07-04', '2026-07-05', '114下學期期末考補考', false],
        ['2025B', '2026-07-15', '2026-07-15', '114下學期期末考成績開放網路查詢', false],
        ['2025B', '2026-07-31', '2026-07-31', '114下學期結束', false],
        ['2026C', '2026-07-04', '2026-07-05', '114下學期期末考補考', false],
        ['2026C', '2026-07-06', '2026-07-06', '115暑期課程開播', true],
        ['2026C', '2026-07-15', '2026-07-15', '114下學期期末考成績開放網路查詢', false],
        ['2026C', '2026-07-31', '2026-07-31', '114下學期結束', false],
        ['2026C', '2026-09-05', '2026-09-06', '115暑期期末考', true],
        ['2026C', '2026-09-12', '2026-09-13', '115暑期期末考補考', false],
        ['2026A', '2026-08-01', '2026-08-01', '115上學期開始', false],
        ['2026A', '2026-09-07', '2026-09-07', '115上學期課程開播', true],
        ['2026A', '2026-09-12', '2026-09-13', '115暑期期末考補考', false],
        ['2026A', '2026-10-15', '2026-10-17', '115上學期畢業申請', false],
        ['2026A', '2026-11-07', '2026-11-08', '115上學期期中考', true],
        ['2026A', '2026-12-01', '2026-12-20', '115下學期選課', false],
        ['2026A', '2027-01-09', '2027-01-10', '115上學期期末考', true],
    ];

    public function up(): void
    {
        $now = now();

        DB::table('school_calendar_events')->insert(array_map(
            static fn (array $event): array => [
                'term' => $event[0],
                'start_date' => $event[1],
                'end_date' => $event[2],
                'name' => $event[3],
                'is_countdown' => $event[4],
                'is_important' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            self::EVENTS,
        ));
    }

    public function down(): void
    {
        DB::table('school_calendar_events')->truncate();
    }
};

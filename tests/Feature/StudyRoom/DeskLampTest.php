<?php

use App\Models\StudentSchedule;
use App\Models\StudyRoomProfile;

/**
 * Pulls the lamp's shade opening and bulb out of the rendered page.
 *
 * @return array<string, array{cx: float, cy: float, r: float}>
 */
function studyRoomLampParts(string $html): array
{
    $parts = [];

    foreach (['shade-mouth', 'bulb'] as $part) {
        preg_match_all(
            '/<(?:ellipse|circle)\b[^>]*data-testid="study-room-lamp-'.$part.'"[^>]*>/s',
            $html,
            $tags
        );

        // The same lamp is drawn three times: once on the control panel
        // before a timer starts, once while it runs, and once in focus mode.
        expect($tags[0])->toHaveCount(3);

        foreach ($tags[0] as $tag) {
            $attribute = function (string $name) use ($tag): float {
                preg_match('/\b'.$name.'="([\d.]+)"/', $tag, $value);

                return (float) ($value[1] ?? 0);
            };

            $parts[$part][] = [
                'cx' => $attribute('cx'),
                'cy' => $attribute('cy'),
                // A circle has r; the shade opening is an ellipse, whose
                // tightest dimension is what has to clear the bulb.
                'r' => $attribute('r') ?: min($attribute('rx'), $attribute('ry')),
            ];
        }
    }

    return $parts;
}

it('keeps the bulb inside the lamp shade', function () {
    $schedule = StudentSchedule::factory()->create();
    StudyRoomProfile::factory()->for($schedule, 'schedule')->create();

    $html = $this->withCredentials()
        ->withCookie('student_schedule', json_encode([
            'id' => $schedule->id,
            'uuid' => $schedule->uuid,
            'name' => $schedule->name,
        ]))
        ->get(route('study-room.show'))
        ->assertOk()
        ->getContent();

    $parts = studyRoomLampParts($html);

    foreach ($parts['bulb'] as $index => $bulb) {
        $mouth = $parts['shade-mouth'][$index];

        expect($bulb['r'])->toBeGreaterThan(0);
        expect($mouth['r'])->toBeGreaterThan(0);

        $offset = sqrt(
            ($bulb['cx'] - $mouth['cx']) ** 2 + ($bulb['cy'] - $mouth['cy']) ** 2
        );

        // The whole bulb has to fit within the shade opening — the old lamp
        // hung it off the outside edge of the shade.
        expect($offset + $bulb['r'])->toBeLessThanOrEqual($mouth['r']);
    }
});

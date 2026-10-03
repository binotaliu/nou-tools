<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ScheduleDevice;
use App\Models\StudentSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ScheduleDevice>
 */
final class ScheduleDeviceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_schedule_id' => StudentSchedule::factory(),
            'token_hash' => ScheduleDevice::hashToken(Str::random(64)),
            'user_agent' => null,
            'is_persistent' => true,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(400),
        ];
    }

    /**
     * A device whose raw token is known, so tests can send it as the cookie.
     */
    public function withToken(string $token): self
    {
        return $this->state(['token_hash' => ScheduleDevice::hashToken($token)]);
    }

    public function expired(): self
    {
        return $this->state(['expires_at' => now()->subMinute()]);
    }
}

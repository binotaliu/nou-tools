<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AcornTransactionKind;
use App\Models\AcornTransaction;
use App\Models\StudentSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<AcornTransaction>
 */
final class AcornTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_schedule_id' => StudentSchedule::factory(),
            'kind' => AcornTransactionKind::StudyFocus,
            'amount' => 5,
            'remaining' => 5,
            'earned_on' => Date::now()->toDateString(),
            'expires_at' => Date::now()->addDays(30),
        ];
    }

    public function credit(int $amount, ?int $expiresInDays = 30): self
    {
        return $this->state(fn (): array => [
            'amount' => $amount,
            'remaining' => $amount,
            'expires_at' => Date::now()->addDays($expiresInDays ?? 30),
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => ['expires_at' => Date::now()->subDay()]);
    }
}

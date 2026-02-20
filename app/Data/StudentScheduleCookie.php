<?php

namespace App\Data;

use App\Models\StudentSchedule;

/**
 * Lightweight data class representing the decrypted/validated
 * `student_schedule` cookie payload.
 */
final readonly class StudentScheduleCookie
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $token,
        public ?string $name,
    ) {}

    public static function fromModel(StudentSchedule $model): self
    {
        return new self(
            $model->id,
            $model->uuid,
            (string) $model->getRouteKey(),
            $model->name,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'token' => $this->token,
            'name' => $this->name,
        ];
    }
}

<?php

namespace NouTools\Domains\Schedules\ViewModels;

use App\Models\StudentSchedule;

final readonly class StudentScheduleCookieViewModel
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
            id: $model->id,
            uuid: $model->uuid,
            token: (string) $model->getRouteKey(),
            name: $model->name,
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

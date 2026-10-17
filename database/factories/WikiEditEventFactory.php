<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WikiEditEventType;
use App\Models\WikiEditEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WikiEditEvent>
 */
final class WikiEditEventFactory extends Factory
{
    protected $model = WikiEditEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event' => WikiEditEventType::Requested,
            'request_uuid' => (string) Str::uuid(),
            'instruction' => '請補充期中考的考試時間。',
        ];
    }
}

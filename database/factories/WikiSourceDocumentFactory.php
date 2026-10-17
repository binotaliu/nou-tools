<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WikiSourceDocumentStatus;
use App\Models\WikiSourceDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WikiSourceDocument>
 */
final class WikiSourceDocumentFactory extends Factory
{
    protected $model = WikiSourceDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'series' => 'course-selection-notes',
            'term' => '115-1',
            'title' => '測試選課注意事項',
            'url' => 'https://www.nou.edu.tw/files/test-'.fake()->unique()->numberBetween(1, 99999).'.pdf',
            'status' => WikiSourceDocumentStatus::Pending,
        ];
    }

    public function ready(): self
    {
        return $this->state(fn (): array => [
            'status' => WikiSourceDocumentStatus::Ready,
            'fetched_at' => now(),
            'page_count' => 10,
        ]);
    }
}

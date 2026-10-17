<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WikiSourceDocument;
use App\Models\WikiSourceSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WikiSourceSection>
 */
final class WikiSourceSectionFactory extends Factory
{
    protected $model = WikiSourceSection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $body = '測試段落內容'.fake()->unique()->numberBetween(1, 99999);

        return [
            'wiki_source_document_id' => WikiSourceDocument::factory(),
            'key' => 'section-'.fake()->unique()->numberBetween(1, 99999),
            'title' => '測試段落',
            'body' => $body,
            'page_start' => 1,
            'page_end' => 1,
            'position' => 0,
            'content_hash' => hash('sha256', $body),
        ];
    }
}

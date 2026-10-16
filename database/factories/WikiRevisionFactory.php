<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WikiRevisionOrigin;
use App\Models\WikiPage;
use App\Models\WikiRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WikiRevision>
 */
final class WikiRevisionFactory extends Factory
{
    protected $model = WikiRevision::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wiki_page_id' => WikiPage::factory(),
            'title' => '測試條目'.fake()->unique()->numberBetween(1, 99999),
            'description' => '這是一個測試用的條目。',
            'body' => "這是一個測試用的條目。\n\n## 段落\n\n內容。\n",
            'categories' => [],
            'edit_summary' => '建立條目',
            'origin' => WikiRevisionOrigin::Admin,
        ];
    }
}

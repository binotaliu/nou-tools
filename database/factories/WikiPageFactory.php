<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WikiPage;
use App\Models\WikiRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Creates a bare page; chain `live()` to give it a current revision.
 *
 * @extends Factory<WikiPage>
 */
final class WikiPageFactory extends Factory
{
    protected $model = WikiPage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'is_protected' => false,
        ];
    }

    /**
     * Adds one revision with the given attributes and makes it current.
     *
     * @param  array<string, mixed>  $revision
     */
    public function live(array $revision = []): static
    {
        return $this->afterCreating(function (WikiPage $page) use ($revision): void {
            $created = WikiRevision::factory()->for($page, 'page')->create($revision);

            $page->current_revision_id = $created->id;
            $page->save();
        });
    }

    public function protected(): static
    {
        return $this->state(fn (): array => ['is_protected' => true]);
    }
}

<?php

declare(strict_types=1);

use App\Models\WikiSourceDocument;
use App\Models\WikiSourceSection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('treats the latest ready term of a series as current', function () {
    $older = WikiSourceDocument::factory()->ready()->create(['term' => '114-2']);
    $latest = WikiSourceDocument::factory()->ready()->create(['term' => '115-1']);
    WikiSourceDocument::factory()->create(['term' => '115-2']);
    $other = WikiSourceDocument::factory()->ready()->create(['series' => 'exam-schedule', 'term' => '114-2']);

    expect(WikiSourceDocument::query()->current()->pluck('id')->all())
        ->toEqualCanonicalizing([$latest->id, $other->id])
        ->not->toContain($older->id);
});

it('does not treat a superseded evergreen document as current', function () {
    $old = WikiSourceDocument::factory()->ready()->create([
        'series' => 'academic-regulations',
        'term' => null,
        'effective_to' => '2026-01-31',
    ]);
    $new = WikiSourceDocument::factory()->ready()->create([
        'series' => 'academic-regulations',
        'term' => null,
        'effective_from' => '2026-02-01',
    ]);

    expect(WikiSourceDocument::query()->current()->pluck('id')->all())
        ->toBe([$new->id])
        ->not->toContain($old->id);
});

it('returns sections in position order and removes them with the document', function () {
    $document = WikiSourceDocument::factory()->ready()->create();
    WikiSourceSection::factory()->for($document, 'document')->create(['key' => 'b', 'position' => 1]);
    WikiSourceSection::factory()->for($document, 'document')->create(['key' => 'a', 'position' => 0]);

    expect($document->sections->pluck('key')->all())->toBe(['a', 'b']);

    $document->delete();

    expect(WikiSourceSection::query()->count())->toBe(0);
});

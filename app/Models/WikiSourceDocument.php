<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WikiSourceDocumentStatus;
use Carbon\CarbonInterface;
use Database\Factories\WikiSourceDocumentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A school document (e.g. 選課注意事項 of one semester) fetched once and split
 * into {@see WikiSourceSection}s, so the wiki editor can read just the parts a
 * request touches instead of the whole file.
 *
 * @property int $id
 * @property string $series
 * @property string|null $term
 * @property string $title
 * @property string $url
 * @property WikiSourceDocumentStatus $status
 * @property string|null $failure_reason
 * @property int|null $page_count
 * @property CarbonInterface|null $fetched_at
 * @property CarbonInterface|null $effective_from
 * @property CarbonInterface|null $effective_to
 */
final class WikiSourceDocument extends Model
{
    /** @use HasFactory<WikiSourceDocumentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WikiSourceDocumentStatus::class,
            'fetched_at' => 'datetime',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    /**
     * The document of its series the editor should default to: ready, not
     * superseded (`effective_to` unset) and with no later term in the series.
     *
     * @param  Builder<WikiSourceDocument>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $query
            ->where('status', WikiSourceDocumentStatus::Ready)
            ->whereNull('effective_to')
            ->whereNotExists(function ($newer): void {
                $newer->from('wiki_source_documents as newer')
                    ->whereColumn('newer.series', 'wiki_source_documents.series')
                    ->whereColumn('newer.term', '>', 'wiki_source_documents.term')
                    ->where('newer.status', WikiSourceDocumentStatus::Ready->value)
                    ->whereNull('newer.effective_to');
            });
    }

    /**
     * @return HasMany<WikiSourceSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(WikiSourceSection::class)->orderBy('position');
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WikiSourceSectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One part of a {@see WikiSourceDocument}, keyed by a stable `key` within it
 * so the same section can be matched across semesters.
 *
 * @property int $id
 * @property int $wiki_source_document_id
 * @property string $key
 * @property string $title
 * @property string $body
 * @property int $page_start
 * @property int $page_end
 * @property int $position
 * @property string $content_hash
 */
final class WikiSourceSection extends Model
{
    /** @use HasFactory<WikiSourceSectionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<WikiSourceDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(WikiSourceDocument::class, 'wiki_source_document_id');
    }
}

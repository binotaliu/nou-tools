<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A `[[slug]]` link found in a page's current revision. Derived (see
 * SyncWikiPageIndexes); powers 連結到此頁.
 *
 * @property int $from_page_id
 * @property string $to_slug
 */
final class WikiLink extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    /**
     * @return BelongsTo<WikiPage, $this>
     */
    public function fromPage(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'from_page_id');
    }
}

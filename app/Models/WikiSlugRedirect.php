<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A slug a page used to have, kept so old links and `[[old-slug]]` keep working.
 *
 * @property string $old_slug
 * @property int $wiki_page_id
 */
final class WikiSlugRedirect extends Model
{
    /**
     * @return BelongsTo<WikiPage, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }
}

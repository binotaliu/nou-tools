<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A category name. Membership is derived from each page's current revision
 * (see SyncWikiPageIndexes) and never edited directly.
 *
 * @property string $name
 */
final class WikiCategory extends Model
{
    /**
     * @return BelongsToMany<WikiPage, $this>
     */
    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(WikiPage::class, 'wiki_category_page');
    }

    public function getRouteKeyName(): string
    {
        return 'name';
    }
}

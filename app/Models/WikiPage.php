<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WikiPageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A wiki page. Its content lives in immutable {@see WikiRevision}s; the page
 * only holds the URL key (a Latin slug, while the title is free text) and
 * which revision is live.
 *
 * @property string $slug
 * @property int|null $current_revision_id
 * @property bool $is_protected
 */
final class WikiPage extends Model
{
    /** @use HasFactory<WikiPageFactory> */
    use HasFactory;

    use SoftDeletes;

    public const string SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public const int SLUG_MAX_LENGTH = 60;

    /**
     * Path segments the wiki's own routes use under `/wiki/`, so no page may
     * take them as a slug.
     */
    public const array RESERVED_SLUGS = [
        'new',
        'category',
        'categories',
        'edit-requests',
        'recent-changes',
        'search',
        'random',
        'history',
        'revisions',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_protected' => 'boolean',
        ];
    }

    public static function isValidSlug(string $slug): bool
    {
        return strlen($slug) <= self::SLUG_MAX_LENGTH
            && preg_match(self::SLUG_PATTERN, $slug) === 1
            && ! in_array($slug, self::RESERVED_SLUGS, true);
    }

    /**
     * Whether a slug is free for a new page: neither a page (deleted ones
     * included, their URL may come back) nor a redirect left by a rename.
     */
    public static function isSlugAvailable(string $slug): bool
    {
        return self::isValidSlug($slug)
            && ! self::withTrashed()->where('slug', $slug)->exists()
            && ! WikiSlugRedirect::query()->where('old_slug', $slug)->exists();
    }

    /**
     * @return BelongsTo<WikiRevision, $this>
     */
    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(WikiRevision::class, 'current_revision_id');
    }

    /**
     * @return HasMany<WikiRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(WikiRevision::class)->orderByDesc('id');
    }

    /**
     * @return BelongsToMany<WikiCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(WikiCategory::class, 'wiki_category_page')->orderBy('name');
    }

    /**
     * @return HasMany<WikiLink, $this>
     */
    public function outgoingLinks(): HasMany
    {
        return $this->hasMany(WikiLink::class, 'from_page_id');
    }

    /**
     * @return HasMany<WikiSlugRedirect, $this>
     */
    public function slugRedirects(): HasMany
    {
        return $this->hasMany(WikiSlugRedirect::class);
    }

    /**
     * @return HasMany<WikiEditRequest, $this>
     */
    public function editRequests(): HasMany
    {
        return $this->hasMany(WikiEditRequest::class);
    }

    /**
     * Pages that have something to show.
     *
     * @param  Builder<WikiPage>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->whereNotNull('current_revision_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NewsletterIssueStatus;
use App\Enums\NewsletterSection;
use Database\Factories\NewsletterIssueFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class NewsletterIssue extends Model
{
    /** @use HasFactory<NewsletterIssueFactory> */
    use HasFactory;

    /** Scoped filesystem disk (see config/filesystems.php) holding `cover_image` files, relative to its own directory. */
    public const string COVER_DISK = 'newsletter_covers';

    /**
     * Origin (scheme://host) cover images are served from when the cover disk is
     * remote, e.g. a CloudFront domain; null while they're served by this app.
     */
    public static function coverImageOrigin(): ?string
    {
        $parent = config('filesystems.disks.'.self::COVER_DISK.'.disk');
        $disk = config("filesystems.disks.{$parent}");

        if (($disk['driver'] ?? null) !== 's3' || blank($disk['url'] ?? null)) {
            return null;
        }

        $parts = parse_url((string) $disk['url']);

        return isset($parts['scheme'], $parts['host']) ? "{$parts['scheme']}://{$parts['host']}" : null;
    }

    protected $fillable = [
        'issue_key',
        'publishes_on',
        'title',
        'cover_image',
        'cover_image_credit_name',
        'cover_image_credit_url',
        'covers_from',
        'covers_to',
        'highlights_from',
        'highlights_to',
        'highlights_intro',
        'highlights_events',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publishes_on' => 'date',
            'covers_from' => 'date',
            'covers_to' => 'date',
            'highlights_from' => 'date',
            'highlights_to' => 'date',
            'highlights_events' => 'array',
            'status' => NewsletterIssueStatus::class,
            'published_at' => 'datetime',
            'ai_drafted_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<NewsletterItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(NewsletterItem::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<NewsletterItem, $this>
     */
    public function newsItems(): HasMany
    {
        return $this->items()->where('section', NewsletterSection::News);
    }

    /**
     * @return HasMany<NewsletterItem, $this>
     */
    public function artItems(): HasMany
    {
        return $this->items()->where('section', NewsletterSection::Arts);
    }

    /**
     * @return HasMany<NewsletterItem, $this>
     */
    public function centerItems(): HasMany
    {
        return $this->items()->where('section', NewsletterSection::Centers);
    }

    /**
     * @return HasMany<NewsletterColumn, $this>
     */
    public function columns(): HasMany
    {
        return $this->hasMany(NewsletterColumn::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @param  Builder<NewsletterIssue>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', NewsletterIssueStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->status === NewsletterIssueStatus::Published;
    }

    public function displayTitle(): string
    {
        return filled($this->title)
            ? (string) $this->title
            : config('newsletter.title').' '.$this->issue_key;
    }

    public function getRouteKeyName(): string
    {
        return 'issue_key';
    }
}

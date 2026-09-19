<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NewsletterSection;
use Database\Factories\NewsletterItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NewsletterItem extends Model
{
    /** @use HasFactory<NewsletterItemFactory> */
    use HasFactory;

    protected $fillable = [
        'newsletter_issue_id',
        'announcement_id',
        'section',
        'source_name',
        'url',
        'headline',
        'summary',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section' => NewsletterSection::class,
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<NewsletterIssue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(NewsletterIssue::class, 'newsletter_issue_id');
    }

    /**
     * @return BelongsTo<Announcement, $this>
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }
}

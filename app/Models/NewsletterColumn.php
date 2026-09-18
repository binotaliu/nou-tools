<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\NewsletterColumnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NewsletterColumn extends Model
{
    /** @use HasFactory<NewsletterColumnFactory> */
    use HasFactory;

    protected $fillable = [
        'newsletter_issue_id',
        'title',
        'author',
        'body',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
}

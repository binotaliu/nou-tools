<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NewsletterReactionType;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NewsletterReaction extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reaction' => NewsletterReactionType::class,
        ];
    }

    /**
     * Identifies a reader across requests without keeping the session id
     * itself in this table.
     */
    public static function sessionHash(Session $session): string
    {
        return hash('sha256', $session->getId());
    }

    /**
     * @return BelongsTo<NewsletterIssue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(NewsletterIssue::class, 'newsletter_issue_id');
    }
}

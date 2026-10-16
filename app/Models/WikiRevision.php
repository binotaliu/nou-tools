<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WikiRevisionOrigin;
use Carbon\CarbonInterface;
use Database\Factories\WikiRevisionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One immutable version of a wiki page. Publishing never edits a revision;
 * it adds a new one and moves {@see WikiPage::$current_revision_id}.
 *
 * @property int $wiki_page_id
 * @property int|null $parent_revision_id
 * @property string $title
 * @property string|null $description
 * @property string $body
 * @property list<string> $categories
 * @property string|null $edit_summary
 * @property WikiRevisionOrigin $origin
 * @property int|null $wiki_edit_request_id
 * @property int|null $user_id
 * @property int|null $student_schedule_id
 * @property CarbonInterface $created_at
 */
final class WikiRevision extends Model
{
    /** @use HasFactory<WikiRevisionFactory> */
    use HasFactory;

    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'categories' => 'array',
            'origin' => WikiRevisionOrigin::class,
        ];
    }

    /**
     * @return BelongsTo<WikiPage, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id')->withTrashed();
    }

    /**
     * @return BelongsTo<WikiRevision, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_revision_id');
    }

    /**
     * @return BelongsTo<WikiEditRequest, $this>
     */
    public function editRequest(): BelongsTo
    {
        return $this->belongsTo(WikiEditRequest::class, 'wiki_edit_request_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

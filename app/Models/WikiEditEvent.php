<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WikiEditEventType;
use Carbon\CarbonInterface;
use Database\Factories\WikiEditEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One step of an edit request's life, kept for auditing how the wiki is
 * used. Append-only.
 *
 * @property WikiEditEventType $event
 * @property int|null $wiki_edit_request_id
 * @property string|null $request_uuid
 * @property string|null $page_slug
 * @property string|null $page_title
 * @property string|null $instruction
 * @property int|null $student_schedule_id
 * @property string|null $ip_hash
 * @property int|null $user_id the admin behind an approval or rejection
 * @property string|null $detail why it ended the way it did
 * @property CarbonInterface $created_at
 */
final class WikiEditEvent extends Model
{
    /** @use HasFactory<WikiEditEventFactory> */
    use HasFactory;

    public const null UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event' => WikiEditEventType::class,
        ];
    }

    /**
     * @return BelongsTo<WikiEditRequest, $this>
     */
    public function editRequest(): BelongsTo
    {
        return $this->belongsTo(WikiEditRequest::class, 'wiki_edit_request_id');
    }

    /**
     * @return BelongsTo<StudentSchedule, $this>
     */
    public function studentSchedule(): BelongsTo
    {
        return $this->belongsTo(StudentSchedule::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

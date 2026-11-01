<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Date;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Ramsey\Uuid\Uuid;

final class StudentSchedule extends Model
{
    use HasFactory;
    use HasPushSubscriptions;
    use Notifiable;

    protected $fillable = [
        'uuid',
        'name',
        'display_options',
        'custom_links',
        'announcement_categories',
        'last_calendar_sync_at',
    ];

    /**
     * Use `uuid` for route model binding (we return a short base64 URL-safe id).
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Return a compact, URL-safe representation of the UUID for route generation.
     */
    public function getRouteKey(): string
    {
        return base64_url_encode(Uuid::fromString((string) $this->uuid)->getBytes());
    }

    /**
     * Accept either canonical UUID or the short base64-url token when resolving routes.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        // canonical UUID string (36 chars, contains dashes)
        if (is_string($value) && str_contains($value, '-') && strlen($value) === 36) {
            return $this->where('uuid', $value)->first();
        }

        // try decode base64-url short form (16 bytes -> 22 chars without padding)
        $bytes = base64_url_decode($value);
        if ($bytes !== false && strlen($bytes) === 16) {
            $uuid = Uuid::fromBytes($bytes)->toString();

            return $this->where('uuid', $uuid)->first();
        }

        return null;
    }

    /**
     * @return HasMany<StudentScheduleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(StudentScheduleItem::class);
    }

    protected $casts = [
        'display_options' => 'json',
        'custom_links' => 'json',
        'announcement_categories' => 'json',
        'last_calendar_sync_at' => 'datetime',
        'wiki_blocked_at' => 'datetime',
        'cafe_blocked_at' => 'datetime',
        'notify_on_class_start' => 'boolean',
    ];

    /**
     * @return HasMany<LearningProgress, $this>
     */
    public function learningProgresses(): HasMany
    {
        return $this->hasMany(LearningProgress::class);
    }

    /**
     * @return HasMany<GradeCalculation, $this>
     */
    public function gradeCalculations(): HasMany
    {
        return $this->hasMany(GradeCalculation::class);
    }

    /**
     * @return HasOne<StudyRoomProfile, $this>
     */
    public function studyRoomProfile(): HasOne
    {
        return $this->hasOne(StudyRoomProfile::class);
    }

    /**
     * @return HasOne<StudyRoomSeat, $this>
     */
    public function studyRoomSeat(): HasOne
    {
        return $this->hasOne(StudyRoomSeat::class);
    }

    /**
     * The coffee currently on the student's seat: their latest order that
     * has not run out yet.
     *
     * @return HasOne<CafeOrder, $this>
     */
    public function activeCafeOrder(): HasOne
    {
        return $this->hasOne(CafeOrder::class)->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->where('expires_at', '>', Date::now()),
        );
    }

    /**
     * @return HasMany<CafeOrder, $this>
     */
    public function cafeOrders(): HasMany
    {
        return $this->hasMany(CafeOrder::class);
    }

    /**
     * Trusted wiki editors (WIKI_UNLIMITED_SCHEDULE_IDS) skip the draft rate
     * limits and the review queue.
     */
    public static function isUnlimitedWikiEditor(?int $scheduleId): bool
    {
        return $scheduleId !== null
            && in_array($scheduleId, config('wiki.unlimited_schedule_ids'), true);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Ramsey\Uuid\Uuid;

class StudentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
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
        return base64_url_encode(Uuid::fromString($this->uuid)->getBytes());
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

    /**
     * @return HasMany<LearningProgress, $this>
     */
    public function learningProgresses(): HasMany
    {
        return $this->hasMany(LearningProgress::class);
    }
}

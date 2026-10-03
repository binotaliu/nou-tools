<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ScheduleDeviceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NouTools\Domains\Schedules\DataTransferObjects\IssueScheduleDeviceData;

/**
 * A browser or installed app that is signed in to a student schedule. Only the
 * sha256 of its token is stored, so a leaked database cannot sign anyone in.
 *
 * @property int $student_schedule_id
 * @property string $token_hash
 * @property string|null $user_agent
 * @property bool $is_persistent
 * @property CarbonInterface $last_used_at
 * @property CarbonInterface $expires_at
 */
final class ScheduleDevice extends Model
{
    /** @use HasFactory<ScheduleDeviceFactory> */
    use HasFactory;

    use MassPrunable;

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * A short public label for a token, safe to put in page props: it lets the
     * browser tell whether its local backup matches the cookie without the
     * token itself ever being shipped with the page.
     */
    public static function fingerprint(string $tokenHash): string
    {
        return substr($tokenHash, 0, 12);
    }

    public function fillFromDTO(IssueScheduleDeviceData $data): self
    {
        $this->student_schedule_id = $data->studentScheduleId;
        $this->token_hash = self::hashToken($data->token);
        $this->user_agent = $data->userAgent;
        $this->is_persistent = $data->isPersistent;
        $this->last_used_at = $data->issuedAt;
        $this->expires_at = $data->expiresAt;

        return $this;
    }

    /**
     * @return BelongsTo<StudentSchedule, $this>
     */
    public function studentSchedule(): BelongsTo
    {
        return $this->belongsTo(StudentSchedule::class);
    }

    /**
     * @param  Builder<ScheduleDevice>  $query
     * @return Builder<ScheduleDevice>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * @return Builder<ScheduleDevice>
     */
    public function prunable(): Builder
    {
        return self::query()->where('expires_at', '<', now()->subDays(7));
    }

    protected function casts(): array
    {
        return [
            'is_persistent' => 'boolean',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}

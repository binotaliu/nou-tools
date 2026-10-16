<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WikiEditRequestStatus;
use App\Enums\WikiEditRisk;
use Carbon\CarbonInterface;
use Database\Factories\WikiEditRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\Wiki\DataTransferObjects\CreateWikiEditRequestDTO;

/**
 * A visitor's description of how a page should change (or what a new page
 * should say), plus the draft the AI editor wrote for it.
 *
 * @property string $uuid
 * @property int|null $wiki_page_id
 * @property string|null $requested_slug
 * @property string|null $requested_title
 * @property int|null $base_revision_id
 * @property string $instruction
 * @property list<string> $source_urls
 * @property list<string> $refinements
 * @property int|null $student_schedule_id
 * @property string $ip_hash
 * @property WikiEditRequestStatus $status
 * @property string|null $draft_slug
 * @property string|null $draft_title
 * @property string|null $draft_description
 * @property string|null $draft_body
 * @property list<string>|null $draft_categories
 * @property string|null $draft_edit_summary
 * @property array{decision?: string, declineReason?: string|null, risk?: string, riskReasons?: list<string>, sourcesUsed?: list<string>}|null $ai_verdict
 * @property int|null $reviewed_by
 * @property CarbonInterface|null $reviewed_at
 * @property string|null $review_note
 * @property int|null $published_revision_id
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
final class WikiEditRequest extends Model
{
    /** @use HasFactory<WikiEditRequestFactory> */
    use HasFactory;

    use MassPrunable;

    protected $attributes = [
        'status' => 'drafting',
        'source_urls' => '[]',
        'refinements' => '[]',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_urls' => 'array',
            'refinements' => 'array',
            'draft_categories' => 'array',
            'ai_verdict' => 'array',
            'status' => WikiEditRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * The visitor's part of a new request; the caller links page, base
     * revision, schedule and ip.
     */
    public function fillFromDTO(CreateWikiEditRequestDTO $data): self
    {
        $this->instruction = trim($data->instruction);
        $this->source_urls = array_values(array_unique(array_map('trim', $data->sourceUrls)));
        $this->requested_slug = filled($data->requestedSlug) ? $data->requestedSlug : null;
        $this->requested_title = filled($data->requestedTitle) ? trim((string) $data->requestedTitle) : null;

        return $this;
    }

    public static function hashIp(?string $ip): string
    {
        return hash_hmac('sha256', (string) $ip, (string) config('app.key'));
    }

    public function isNewPage(): bool
    {
        return $this->wiki_page_id === null;
    }

    public function risk(): ?WikiEditRisk
    {
        return WikiEditRisk::tryFrom((string) ($this->ai_verdict['risk'] ?? ''));
    }

    public function declineReason(): ?string
    {
        return $this->ai_verdict['declineReason'] ?? null;
    }

    public function belongsToSchedule(?StudentScheduleCookie $viewer): bool
    {
        return $viewer !== null && $this->student_schedule_id === $viewer->id;
    }

    /**
     * @return BelongsTo<WikiPage, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(WikiPage::class, 'wiki_page_id');
    }

    /**
     * @return BelongsTo<WikiRevision, $this>
     */
    public function baseRevision(): BelongsTo
    {
        return $this->belongsTo(WikiRevision::class, 'base_revision_id');
    }

    /**
     * @return BelongsTo<WikiRevision, $this>
     */
    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(WikiRevision::class, 'published_revision_id');
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
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Requests nobody finished: drafts never confirmed, or still "drafting"
     * because the process died, a week after they were last touched.
     *
     * @return Builder<WikiEditRequest>
     */
    public function prunable(): Builder
    {
        return self::query()
            ->whereIn('status', [
                WikiEditRequestStatus::Drafting,
                WikiEditRequestStatus::AwaitingConfirmation,
                WikiEditRequestStatus::Conflict,
                WikiEditRequestStatus::Abandoned,
                WikiEditRequestStatus::Failed,
            ])
            ->where('updated_at', '<', now()->subDays(7));
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}

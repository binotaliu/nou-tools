<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WikiEditRequestStatus;
use App\Models\StudentSchedule;
use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use App\Models\WikiSourceDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WikiEditRequest>
 */
final class WikiEditRequestFactory extends Factory
{
    protected $model = WikiEditRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'instruction' => '請補充期中考的考試時間。',
            'source_urls' => [],
            'refinements' => [],
            'student_schedule_id' => StudentSchedule::factory(),
            'ip_hash' => WikiEditRequest::hashIp('127.0.0.1'),
            'status' => WikiEditRequestStatus::Drafting,
        ];
    }

    /**
     * A semester rollover request: no visitor, so no schedule or ip.
     */
    public function rollover(WikiSourceDocument $document): static
    {
        return $this->state(fn (): array => [
            'student_schedule_id' => null,
            'ip_hash' => null,
            'wiki_source_document_id' => $document->id,
        ]);
    }

    /**
     * An edit of an existing page, based on its current revision.
     */
    public function forPage(WikiPage $page): static
    {
        return $this->state(fn (): array => [
            'wiki_page_id' => $page->id,
            'base_revision_id' => $page->current_revision_id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    public function awaitingConfirmation(array $draft = []): static
    {
        return $this->state(fn (): array => [
            'status' => WikiEditRequestStatus::AwaitingConfirmation,
            'draft_slug' => null,
            'draft_title' => '測試條目',
            'draft_description' => '這是一個測試用的條目。',
            'draft_body' => "這是一個測試用的條目。\n\n## 段落\n\n更新後的內容。\n",
            'draft_categories' => [],
            'draft_edit_summary' => '更新段落內容',
            'ai_verdict' => [
                'decision' => 'apply',
                'declineReason' => null,
                'risk' => 'low',
                'riskReasons' => [],
                'sourcesUsed' => [],
            ],
            ...$draft,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Ai;

use App\Enums\WikiEditRisk;

/**
 * What {@see WikiEditor} returned, normalised: trimmed strings, string lists
 * without blanks or duplicates, and an unknown risk read as high.
 */
final readonly class WikiEditorDraft
{
    /**
     * @param  list<string>  $categories
     * @param  list<string>  $riskReasons
     * @param  list<string>  $sourcesUsed
     */
    public function __construct(
        public bool $applies,
        public string $declineReason,
        public string $slug,
        public string $title,
        public string $description,
        public string $body,
        public array $categories,
        public string $editSummary,
        public WikiEditRisk $risk,
        public array $riskReasons,
        public array $sourcesUsed,
    ) {}

    /**
     * @param  array<string, mixed>|\ArrayAccess<string, mixed>  $response
     */
    public static function fromResponse(array|\ArrayAccess $response): self
    {
        return new self(
            applies: ($response['decision'] ?? null) === 'apply',
            declineReason: self::text($response['decline_reason'] ?? ''),
            slug: strtolower(self::text($response['slug'] ?? '')),
            title: self::text($response['title'] ?? ''),
            description: self::text($response['description'] ?? ''),
            body: rtrim(str_replace("\r\n", "\n", (string) ($response['body'] ?? '')))."\n",
            categories: self::list($response['categories'] ?? []),
            editSummary: self::text($response['edit_summary'] ?? ''),
            risk: WikiEditRisk::tryFrom((string) ($response['risk'] ?? '')) ?? WikiEditRisk::High,
            riskReasons: self::list($response['risk_reasons'] ?? []),
            sourcesUsed: self::list($response['sources_used'] ?? []),
        );
    }

    /**
     * Stored as `wiki_edit_requests.ai_verdict`.
     *
     * @return array{decision: string, declineReason: string|null, risk: string, riskReasons: list<string>, sourcesUsed: list<string>}
     */
    public function verdict(): array
    {
        return [
            'decision' => $this->applies ? 'apply' : 'decline',
            'declineReason' => $this->applies ? null : $this->declineReason,
            'risk' => $this->risk->value,
            'riskReasons' => $this->riskReasons,
            'sourcesUsed' => $this->sourcesUsed,
        ];
    }

    private static function text(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    /**
     * @return list<string>
     */
    private static function list(mixed $values): array
    {
        if (! is_iterable($values)) {
            return [];
        }

        $items = [];

        foreach ($values as $value) {
            $value = self::text($value);

            if ($value !== '' && ! in_array($value, $items, true)) {
                $items[] = $value;
            }
        }

        return $items;
    }
}

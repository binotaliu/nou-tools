<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Enums\BaristaTrigger;
use App\Models\CafeBaristaLine;
use Illuminate\Support\Collection;
use NouTools\Domains\Cafe\ValueObjects\BaristaContext;
use NouTools\Domains\Cafe\ViewModels\BaristaLineViewModel;

/**
 * Chooses what the barista says. Lines whose trigger matches the context are
 * grouped by priority; the highest tier that still has a line not shown
 * recently wins, and weight picks within it. Placeholders are filled here, and
 * a line that needs one the context cannot supply is skipped.
 */
final readonly class PickBaristaLine
{
    /**
     * @param  array<int, int>  $recentLineIds
     */
    public function __invoke(BaristaContext $context, bool $afterOrder, array $recentLineIds = []): ?BaristaLineViewModel
    {
        $eligible = CafeBaristaLine::query()
            ->active()
            ->get()
            ->filter(fn (CafeBaristaLine $line): bool => $line->trigger->isAfterOrder() === $afterOrder)
            ->filter(fn (CafeBaristaLine $line): bool => $this->matches($line, $context))
            ->filter(fn (CafeBaristaLine $line): bool => $this->canFill($line, $context->placeholders($line->trigger)))
            ->values();

        $line = $this->chooseFrom($eligible, $recentLineIds) ?? $this->chooseFrom($eligible, []);

        if ($line === null) {
            return null;
        }

        $placeholders = $context->placeholders($line->trigger);

        return new BaristaLineViewModel(
            id: $line->id,
            trigger: $line->trigger->value,
            text: $this->fill($line->text, $placeholders),
            mood: $line->mood,
            replies: array_map(fn (array $reply): array => [
                'label' => (string) ($reply['label'] ?? ''),
                'response' => $this->fill((string) ($reply['response'] ?? ''), $placeholders),
                'action' => (string) ($reply['action'] ?? 'none'),
            ], array_slice($line->replies ?? [], 0, 3)),
        );
    }

    /**
     * @param  Collection<int, CafeBaristaLine>  $lines
     * @param  array<int, int>  $excludedIds
     */
    private function chooseFrom(Collection $lines, array $excludedIds): ?CafeBaristaLine
    {
        $pool = $lines->reject(fn (CafeBaristaLine $line): bool => in_array($line->id, $excludedIds, true));

        foreach ($pool->groupBy('priority')->sortKeysDesc() as $tier) {
            return $this->weightedPick($tier->values());
        }

        return null;
    }

    /**
     * @param  Collection<int, CafeBaristaLine>  $tier
     */
    private function weightedPick(Collection $tier): CafeBaristaLine
    {
        $total = max(1, (int) $tier->sum(fn (CafeBaristaLine $line): int => max(1, $line->weight)));
        $roll = random_int(1, $total);

        foreach ($tier as $line) {
            $roll -= max(1, $line->weight);

            if ($roll <= 0) {
                return $line;
            }
        }

        return $tier->last();
    }

    private function matches(CafeBaristaLine $line, BaristaContext $context): bool
    {
        $conditions = $line->conditions ?? [];

        return match ($line->trigger) {
            BaristaTrigger::Greeting, BaristaTrigger::SmallTalk, BaristaTrigger::OrderThanks => true,
            BaristaTrigger::FirstVisit => $context->isFirstVisit,
            BaristaTrigger::TimeOfDay => ($conditions['period'] ?? null) === $context->period,
            BaristaTrigger::Weekend => $context->isWeekend,
            BaristaTrigger::ExamUpcoming => $context->exam !== null
                && $context->exam['days'] > 0
                && $this->examKindMatches($conditions['exam'] ?? 'any', $context->exam['kind'])
                && $this->within($context->exam['days'], $conditions),
            BaristaTrigger::ExamDay => $context->exam !== null && $context->exam['days'] === 0,
            BaristaTrigger::AfterExam => $context->recentExam !== null
                && $context->recentExam['daysSince'] <= (int) ($conditions['max_days_since'] ?? 3),
            BaristaTrigger::SchoolEventUpcoming => $context->event !== null && $this->within($context->event['days'], $conditions),
            BaristaTrigger::StudiedToday => $context->studyMinutesToday >= (int) ($conditions['min_minutes'] ?? 1)
                && $context->studyMinutesToday > 0,
            BaristaTrigger::AcornsLow => $context->nickname !== null && $context->acorns <= (int) ($conditions['max'] ?? 2),
            BaristaTrigger::AcornsMany => $context->acorns >= (int) ($conditions['min'] ?? 15),
            BaristaTrigger::OfferStickyNote => $context->canPostNote,
        };
    }

    private function examKindMatches(string $wanted, string $actual): bool
    {
        return $wanted === 'any' || $wanted === $actual;
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function within(int $days, array $conditions): bool
    {
        return $days >= (int) ($conditions['min_days'] ?? 0)
            && $days <= (int) ($conditions['max_days'] ?? PHP_INT_MAX);
    }

    /**
     * @param  array<string, string|null>  $placeholders
     */
    private function canFill(CafeBaristaLine $line, array $placeholders): bool
    {
        $texts = [$line->text, ...array_map(fn (array $reply): string => (string) ($reply['response'] ?? ''), $line->replies ?? [])];

        foreach ($texts as $text) {
            preg_match_all('/\{(\w+)\}/', $text, $found);

            foreach ($found[1] as $name) {
                if (($placeholders[$name] ?? null) === null) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  array<string, string|null>  $placeholders
     */
    private function fill(string $text, array $placeholders): string
    {
        return (string) preg_replace_callback(
            '/\{(\w+)\}/',
            fn (array $match): string => $placeholders[$match[1]] ?? '',
            $text,
        );
    }
}

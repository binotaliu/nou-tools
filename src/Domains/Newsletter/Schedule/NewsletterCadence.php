<?php

declare(strict_types=1);

namespace NouTools\Domains\Newsletter\Schedule;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use NouTools\Domains\Newsletter\DataTransferObjects\NewsletterIssueScheduleDTO;

/**
 * Newsletter cadence: an issue every `cadence_days` (14) counted from the
 * configured anchor Monday. Issue keys are the ISO week of the publish
 * Monday (e.g. `2026-W39`), but the cadence is never derived from week
 * number parity, since 53-week ISO years break it.
 */
final readonly class NewsletterCadence
{
    public const string TIMEZONE = 'Asia/Taipei';

    /**
     * Resolve the issue published on the given Monday.
     *
     * @throws InvalidArgumentException when the date isn't an issue Monday
     */
    public function forPublishDate(CarbonInterface|string $publishesOn): NewsletterIssueScheduleDTO
    {
        $date = $this->toDate($publishesOn);

        if (! $this->isIssueDate($date)) {
            throw new InvalidArgumentException("{$date->toDateString()} 不是雙週報的發刊日。");
        }

        $cadenceDays = $this->cadenceDays();

        return new NewsletterIssueScheduleDTO(
            issueKey: $date->format('o-\WW'),
            publishesOn: $date,
            editingStartsOn: $date->subDays(7),
            coversFrom: $date->subDays($cadenceDays),
            coversTo: $date->subDay(),
            highlightsFrom: $date,
            highlightsTo: $date->addDays($cadenceDays - 1),
        );
    }

    /**
     * Resolve an issue from its key, e.g. `2026-W39`.
     *
     * @throws InvalidArgumentException when the key is malformed or off-cadence
     */
    public function forIssueKey(string $issueKey): NewsletterIssueScheduleDTO
    {
        if (! preg_match('/^(\d{4})-W(\d{2})$/', $issueKey, $matches)) {
            throw new InvalidArgumentException("無效的期號：{$issueKey}");
        }

        $monday = Date::now(self::TIMEZONE)
            ->setISODate((int) $matches[1], (int) $matches[2])
            ->startOfDay();

        if ($monday->format('o-\WW') !== $issueKey) {
            throw new InvalidArgumentException("無效的期號：{$issueKey}");
        }

        return $this->forPublishDate($monday);
    }

    /**
     * The first issue published on or after the given date.
     */
    public function nextOnOrAfter(CarbonInterface|string $date): NewsletterIssueScheduleDTO
    {
        $date = $this->toDate($date);
        $anchor = $this->anchor();
        $cadenceDays = $this->cadenceDays();

        if ($date->lte($anchor)) {
            return $this->forPublishDate($anchor);
        }

        $daysSinceAnchor = (int) $anchor->diffInDays($date);
        $steps = intdiv($daysSinceAnchor + $cadenceDays - 1, $cadenceDays);

        return $this->forPublishDate($anchor->addDays($steps * $cadenceDays));
    }

    /**
     * The issue whose editing week starts on the given date, if any.
     */
    public function startingEditingOn(CarbonInterface|string $date): ?NewsletterIssueScheduleDTO
    {
        $publishesOn = $this->toDate($date)->addDays(7);

        return $this->isIssueDate($publishesOn) ? $this->forPublishDate($publishesOn) : null;
    }

    public function isIssueDate(CarbonInterface|string $date): bool
    {
        $date = $this->toDate($date);
        $anchor = $this->anchor();

        if ($date->lt($anchor) || ! $date->isMonday()) {
            return false;
        }

        return ((int) $anchor->diffInDays($date)) % $this->cadenceDays() === 0;
    }

    private function anchor(): CarbonInterface
    {
        return $this->toDate((string) config('newsletter.anchor_date'));
    }

    private function cadenceDays(): int
    {
        return (int) config('newsletter.cadence_days', 14);
    }

    /**
     * Normalise to midnight of the Taipei calendar date. `Date` is bound to
     * CarbonImmutable (see AppServiceProvider), so arithmetic on the result
     * never mutates it.
     */
    private function toDate(CarbonInterface|string $date): CarbonInterface
    {
        $dateString = $date instanceof CarbonInterface
            ? $date->copy()->setTimezone(self::TIMEZONE)->toDateString()
            : $date;

        return Date::parse($dateString, self::TIMEZONE)->startOfDay();
    }
}

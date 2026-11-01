<?php

declare(strict_types=1);

namespace NouTools\Domains\Acorns\Actions;

use App\Enums\AcornTransactionKind;
use App\Models\AcornTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Acorns\Support\AcornExpiry;
use NouTools\Domains\Acorns\ViewModels\AcornBalanceViewModel;
use NouTools\Domains\Acorns\ViewModels\AcornTransactionViewModel;

final readonly class ReadAcornBalance
{
    public function __invoke(int $scheduleId): AcornBalanceViewModel
    {
        $spendable = AcornTransaction::query()
            ->where('student_schedule_id', $scheduleId)
            ->spendable();

        $balance = (int) (clone $spendable)->sum('remaining');

        $nextExpiresAt = (clone $spendable)->min('expires_at');
        $nextExpiryAmount = $nextExpiresAt === null
            ? null
            : (int) (clone $spendable)->where('expires_at', $nextExpiresAt)->sum('remaining');

        $today = AcornExpiry::localDay(Date::now());
        $weekStart = $today->copy()->startOfWeek(CarbonInterface::MONDAY);

        $earned = fn (AcornTransactionKind $kind, string $from, string $until) => (int) AcornTransaction::query()
            ->where('student_schedule_id', $scheduleId)
            ->where('kind', $kind)
            ->where('amount', '>', 0)
            ->where('earned_on', '>=', $from)
            ->where('earned_on', '<=', $until)
            ->sum('amount');

        $todayDate = $today->toDateString();

        $recent = AcornTransaction::query()
            ->where('student_schedule_id', $scheduleId)
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (AcornTransaction $transaction): AcornTransactionViewModel => AcornTransactionViewModel::fromModel($transaction))
            ->all();

        return new AcornBalanceViewModel(
            balance: $balance,
            studyToday: $earned(AcornTransactionKind::StudyFocus, $todayDate, $todayDate),
            studyDailyCap: (int) config('cafe.acorns.study.daily_cap'),
            studyThisWeek: $earned(AcornTransactionKind::StudyFocus, $weekStart->toDateString(), $todayDate),
            studyWeeklyCap: (int) config('cafe.acorns.study.weekly_cap'),
            learningToday: $earned(AcornTransactionKind::LearningProgress, $todayDate, $todayDate),
            learningDailyCap: (int) config('cafe.acorns.learning_progress.daily_cap'),
            nextExpiryAmount: $nextExpiryAmount,
            nextExpiresAt: $nextExpiresAt === null ? null : Date::parse($nextExpiresAt)->toIso8601String(),
            recent: $recent,
        );
    }
}

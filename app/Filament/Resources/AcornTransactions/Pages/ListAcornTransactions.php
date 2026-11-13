<?php

declare(strict_types=1);

namespace App\Filament\Resources\AcornTransactions\Pages;

use App\Filament\Resources\AcornTransactions\AcornTransactionResource;
use App\Models\StudentSchedule;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use NouTools\Domains\Acorns\Actions\GrantAcornsAsAdmin;

final class ListAcornTransactions extends ListRecords
{
    protected static string $resource = AcornTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('grant')
                ->label('發放橡實')
                ->icon('heroicon-o-gift')
                ->schema([
                    Select::make('student_schedule_id')
                        ->label('對象')
                        ->helperText('可搜尋自習室暱稱或課表名稱。')
                        ->searchable()
                        ->required()
                        ->getSearchResultsUsing(fn (string $search): array => StudentSchedule::query()
                            ->where(fn ($query) => $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhereHas('studyRoomProfile', fn ($profile) => $profile->where('nickname', 'like', "%{$search}%")))
                            ->with('studyRoomProfile')
                            ->limit(30)
                            ->get()
                            ->mapWithKeys(fn (StudentSchedule $schedule): array => [$schedule->id => self::scheduleLabel($schedule)])
                            ->all())
                        ->getOptionLabelUsing(fn (mixed $value): ?string => ($schedule = StudentSchedule::query()->with('studyRoomProfile')->find($value)) === null
                            ? null
                            : self::scheduleLabel($schedule)),
                    TextInput::make('amount')
                        ->label('數量')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(1000)
                        ->required(),
                    TextInput::make('reason')
                        ->label('原因')
                        ->maxLength(200),
                ])
                ->action(function (array $data): void {
                    /** @var User $admin */
                    $admin = auth()->user();

                    app(GrantAcornsAsAdmin::class)(
                        (int) $data['student_schedule_id'],
                        (int) $data['amount'],
                        $data['reason'] ?? null,
                        $admin,
                    );

                    Notification::make()->success()->title('已發放橡實')->send();
                }),
        ];
    }

    private static function scheduleLabel(StudentSchedule $schedule): string
    {
        $nickname = $schedule->studyRoomProfile?->nickname;

        return trim(($nickname ?? '（無暱稱）').' · '.($schedule->name ?? '（未命名）')).' #'.$schedule->id;
    }
}

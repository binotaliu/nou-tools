<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyRoomProfiles\Tables;

use App\Models\StudyRoomProfile;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use NouTools\Domains\Schedules\ValueObjects\StudentScheduleCookie;
use NouTools\Domains\StudyRoom\Actions\ForceResetNickname;
use NouTools\Domains\StudyRoom\Actions\LeaveSeat;

final class StudyRoomProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('emoji')
                    ->label('表情符號'),
                TextColumn::make('nickname')
                    ->label('暱稱')
                    ->searchable()
                    ->placeholder('（未設定）'),
                TextColumn::make('schedule.name')
                    ->label('課表名稱')
                    ->placeholder('（未命名）'),
                TextColumn::make('nickname_changed_at')
                    ->label('暱稱變更時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('nickname_reset_at')
                    ->label('上次重設時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('seatState')
                    ->label('目前座位')
                    ->state(fn (StudyRoomProfile $record): string => $record->schedule?->studyRoomSeat?->label ?? '未入座'),
            ])
            ->defaultSort('nickname_changed_at', 'desc')
            ->recordActions([
                Action::make('forceResetNickname')
                    ->label('強制重設暱稱')
                    ->icon('heroicon-o-face-frown')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('將清空此帳號的暱稱，並解除 7 天冷卻限制，讓對方可以立即重新設定。')
                    ->visible(fn (StudyRoomProfile $record): bool => filled($record->nickname))
                    ->action(function (StudyRoomProfile $record): void {
                        /** @var User $admin */
                        $admin = auth()->user();

                        app(ForceResetNickname::class)($record, $admin);

                        Notification::make()
                            ->success()
                            ->title('已重設暱稱')
                            ->send();
                    }),
                Action::make('clearSeat')
                    ->label('清空座位')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('將釋放此帳號目前佔用的座位，讓其他學生可以入座。')
                    ->visible(fn (StudyRoomProfile $record): bool => $record->schedule?->studyRoomSeat !== null)
                    ->action(function (StudyRoomProfile $record): void {
                        app(LeaveSeat::class)(StudentScheduleCookie::fromModel($record->schedule));

                        Notification::make()
                            ->success()
                            ->title('已清空座位')
                            ->send();
                    }),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeStickyNotes\Tables;

use App\Enums\StickyNoteStatus;
use App\Models\CafeStickyNote;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use NouTools\Domains\Cafe\Actions\BlockCafeSchedule;
use NouTools\Domains\Cafe\Actions\DeclineStickyNote;
use NouTools\Domains\Cafe\Actions\HideStickyNote;
use NouTools\Domains\Cafe\Actions\PublishStickyNote;

class CafeStickyNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('drawing')
                    ->label('圖')
                    ->state(fn (CafeStickyNote $record): ?string => $record->drawing_path === null ? null : route('cafe.notes.drawing', $record))
                    ->square()
                    ->size(64),
                TextColumn::make('text')->label('文字')->wrap()->placeholder('（只有圖）'),
                TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->formatStateUsing(fn (StickyNoteStatus $state): string => $state->label())
                    ->color(fn (StickyNoteStatus $state): string => match ($state) {
                        StickyNoteStatus::Published => 'success',
                        StickyNoteStatus::PendingReview => 'warning',
                        StickyNoteStatus::Declined, StickyNoteStatus::Hidden => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('schedule.studyRoomProfile.nickname')->label('暱稱')->placeholder('—'),
                TextColumn::make('ai_verdict.reason')->label('AI 判斷')->wrap()->placeholder('—'),
                TextColumn::make('report_count')->label('檢舉')->sortable(),
                TextColumn::make('created_at')->label('買下時間')->dateTime('m/d H:i')->sortable(),
                TextColumn::make('expires_at')->label('下架時間')->dateTime('m/d H:i')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('狀態')
                    ->options(collect(StickyNoteStatus::cases())->mapWithKeys(fn (StickyNoteStatus $status): array => [$status->value => $status->label()]))
                    ->default(StickyNoteStatus::PendingReview->value),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approve')
                    ->label('通過')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (CafeStickyNote $record): bool => in_array($record->status, [StickyNoteStatus::Screening, StickyNoteStatus::PendingReview, StickyNoteStatus::Hidden], true))
                    ->action(function (CafeStickyNote $record): void {
                        app(PublishStickyNote::class)($record);
                        Notification::make()->title('已貼上牆')->success()->send();
                    }),
                Action::make('decline')
                    ->label('退回並退還橡實')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (CafeStickyNote $record): bool => $record->status !== StickyNoteStatus::Declined)
                    ->action(function (CafeStickyNote $record): void {
                        app(DeclineStickyNote::class)($record);
                        Notification::make()->title('已退回，橡實已退還')->success()->send();
                    }),
                Action::make('hide')
                    ->label('下架')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (CafeStickyNote $record): bool => $record->status === StickyNoteStatus::Published)
                    ->action(function (CafeStickyNote $record): void {
                        app(HideStickyNote::class)($record);
                        Notification::make()->title('已下架')->success()->send();
                    }),
                Action::make('toggleBlock')
                    ->label(fn (CafeStickyNote $record): string => $record->schedule?->cafe_blocked_at === null ? '封鎖貼便利貼' : '解除封鎖')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (CafeStickyNote $record): bool => $record->schedule !== null)
                    ->action(function (CafeStickyNote $record): void {
                        $blocked = $record->schedule->cafe_blocked_at === null;
                        app(BlockCafeSchedule::class)($record->schedule, $blocked);
                        Notification::make()->title($blocked ? '已封鎖這份課表貼便利貼' : '已解除封鎖')->success()->send();
                    }),
            ]);
    }
}

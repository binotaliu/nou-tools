<?php

declare(strict_types=1);

namespace App\Filament\Resources\AcornTransactions\Tables;

use App\Enums\AcornTransactionKind;
use App\Models\AcornTransaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class AcornTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('時間')->dateTime('m/d H:i')->sortable(),
                TextColumn::make('schedule.studyRoomProfile.nickname')->label('暱稱')->placeholder('—')->searchable(),
                TextColumn::make('schedule.name')->label('課表名稱')->placeholder('（未命名）')->searchable(),
                TextColumn::make('kind')
                    ->label('類型')
                    ->badge()
                    ->formatStateUsing(fn (AcornTransactionKind $state): string => $state->label()),
                TextColumn::make('amount')
                    ->label('數量')
                    ->numeric()
                    ->sortable()
                    ->color(fn (int $state): string => $state < 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? '+'.$state : (string) $state),
                TextColumn::make('remaining')->label('剩餘')->placeholder('—'),
                TextColumn::make('expires_at')->label('到期')->dateTime('m/d H:i')->placeholder('—'),
                TextColumn::make('note')
                    ->label('備註')
                    ->state(fn (AcornTransaction $record): ?string => isset($record->meta['reason'])
                        ? $record->meta['reason'].(isset($record->meta['adminName']) ? '（'.$record->meta['adminName'].'）' : '')
                        : ($record->meta['adminName'] ?? null))
                    ->wrap()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->label('類型')
                    ->options(collect(AcornTransactionKind::cases())->mapWithKeys(fn (AcornTransactionKind $kind): array => [$kind->value => $kind->label()])),
                Filter::make('spends')
                    ->label('只看花費')
                    ->query(fn (Builder $query): Builder => $query->where('amount', '<', 0)),
            ])
            ->defaultSort('id', 'desc');
    }
}

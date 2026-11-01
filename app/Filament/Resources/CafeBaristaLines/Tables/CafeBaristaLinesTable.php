<?php

declare(strict_types=1);

namespace App\Filament\Resources\CafeBaristaLines\Tables;

use App\Enums\BaristaTrigger;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CafeBaristaLinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trigger')
                    ->label('情境')
                    ->formatStateUsing(fn (BaristaTrigger $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('text')
                    ->label('內容')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('priority')->label('優先')->sortable(),
                TextColumn::make('weight')->label('權重')->sortable(),
                IconColumn::make('is_active')->label('啟用')->boolean(),
            ])
            ->filters([
                SelectFilter::make('trigger')
                    ->label('情境')
                    ->options(collect(BaristaTrigger::cases())->mapWithKeys(fn (BaristaTrigger $trigger): array => [$trigger->value => $trigger->label()])),
            ])
            ->defaultSort('priority', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Courses\Resources\PreviousExams\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PreviousExamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('term')
                    ->label('學期')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('course_no')
                    ->label('科目代號')
                    ->searchable(),
                IconColumn::make('has_midterm')
                    ->label('期中考')
                    ->state(fn ($record): bool => filled($record->midterm_reference_primary) || filled($record->midterm_reference_secondary))
                    ->boolean(),
                IconColumn::make('has_final')
                    ->label('期末考')
                    ->state(fn ($record): bool => filled($record->final_reference_primary) || filled($record->final_reference_secondary))
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

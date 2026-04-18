<?php

namespace App\Filament\Resources\Courses\Tables;

use App\Models\Course;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('課程名稱')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('term')
                    ->label('學期')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department')
                    ->label('學系')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('credits')
                    ->label('學分')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : $state.' 學分')
                    ->sortable(),
                TextColumn::make('credit_type')
                    ->label('必/選修')
                    ->toggleable(),
                TextColumn::make('classes_count')
                    ->label('班級數')
                    ->formatStateUsing(fn (?int $state): string => (string) ($state ?? 0))
                    ->sortable(),
                IconColumn::make('textbook_exists')
                    ->label('教科書')
                    ->state(fn (Course $record): bool => $record->textbook !== null)
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('更新時間')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('updated_at', 'desc')
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

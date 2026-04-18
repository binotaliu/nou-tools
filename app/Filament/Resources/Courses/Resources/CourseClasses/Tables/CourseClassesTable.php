<?php

namespace App\Filament\Resources\Courses\Resources\CourseClasses\Tables;

use App\Enums\CourseClassType;
use App\Models\CourseClass;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CourseClassesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('班級代碼')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('班級類型')
                    ->badge()
                    ->formatStateUsing(fn (CourseClassType|string|null $state): string => $state instanceof CourseClassType
                        ? $state->label()
                        : (CourseClassType::tryFrom((string) $state)?->label() ?? '未分類')),
                TextColumn::make('teacher_name')
                    ->label('教師')
                    ->searchable(),
                TextColumn::make('start_time')
                    ->label('上課時間')
                    ->formatStateUsing(fn (?string $state, CourseClass $record): string => match (true) {
                        filled($state) && filled($record->end_time) => $state.' - '.$record->end_time,
                        filled($state) => $state,
                        filled($record->end_time) => $record->end_time,
                        default => '未設定',
                    }),
                TextColumn::make('schedules_count')
                    ->label('面授日期數')
                    ->formatStateUsing(fn (?int $state): string => (string) ($state ?? 0)),
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

<?php

namespace App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules\Tables;

use App\Models\ClassSchedule;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClassSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('日期')
                    ->date('Y-m-d')
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('覆寫時間')
                    ->formatStateUsing(fn (?string $state, ClassSchedule $record): string => match (true) {
                        filled($state) && filled($record->end_time) => $state.' - '.$record->end_time,
                        filled($state) => $state,
                        filled($record->end_time) => $record->end_time,
                        default => '使用班級預設時間',
                    }),
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

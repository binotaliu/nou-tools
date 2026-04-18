<?php

namespace App\Filament\Resources\Courses\Resources\CourseClasses\Resources\ClassSchedules\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClassScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('面授日期')
                    ->schema([
                        DatePicker::make('date')
                            ->label('日期')
                            ->required(),
                        TextInput::make('start_time')
                            ->label('覆寫開始時間')
                            ->placeholder('14:00')
                            ->maxLength(255),
                        TextInput::make('end_time')
                            ->label('覆寫結束時間')
                            ->placeholder('15:50')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }
}

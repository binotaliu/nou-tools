<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本資料')
                    ->schema([
                        TextInput::make('name')
                            ->label('課程名稱')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('term')
                            ->label('學期')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('department')
                            ->label('學系')
                            ->maxLength(255),
                        TextInput::make('credit_type')
                            ->label('必/選修')
                            ->maxLength(255),
                        TextInput::make('credits')
                            ->label('學分')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('in_person_class_type')
                            ->label('面授類別')
                            ->maxLength(255),
                        TextInput::make('media')
                            ->label('媒體')
                            ->maxLength(255),
                        TextInput::make('nature')
                            ->label('課程性質')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('外部資訊')
                    ->schema([
                        TextInput::make('description_url')
                            ->label('科目內容連結')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('multimedia_url')
                            ->label('多媒體簡介連結')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('考試資訊')
                    ->schema([
                        DatePicker::make('midterm_date')
                            ->label('期中考日期'),
                        DatePicker::make('final_date')
                            ->label('期末考日期'),
                        TextInput::make('exam_time_start')
                            ->label('考試開始時間')
                            ->placeholder('13:30')
                            ->maxLength(255),
                        TextInput::make('exam_time_end')
                            ->label('考試結束時間')
                            ->placeholder('14:40')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }
}

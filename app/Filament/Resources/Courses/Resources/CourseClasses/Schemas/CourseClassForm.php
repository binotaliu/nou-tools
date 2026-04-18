<?php

namespace App\Filament\Resources\Courses\Resources\CourseClasses\Schemas;

use App\Enums\CourseClassType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('班級資訊')
                    ->schema([
                        TextInput::make('code')
                            ->label('班級代碼')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label('班級類型')
                            ->options(collect(CourseClassType::cases())->mapWithKeys(fn (CourseClassType $type): array => [
                                $type->value => $type->label(),
                            ])->all())
                            ->required(),
                        TextInput::make('teacher_name')
                            ->label('教師姓名')
                            ->maxLength(255),
                        TextInput::make('link')
                            ->label('視訊連結')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('start_time')
                            ->label('預設開始時間')
                            ->placeholder('09:00')
                            ->maxLength(255),
                        TextInput::make('end_time')
                            ->label('預設結束時間')
                            ->placeholder('10:50')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Courses\Resources\PreviousExams\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PreviousExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('考古題資訊')
                    ->schema([
                        TextInput::make('term')
                            ->label('學期')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('course_no')
                            ->label('科目代號')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('midterm_reference_primary')
                            ->label('期中考正參')
                            ->maxLength(255),
                        TextInput::make('midterm_reference_secondary')
                            ->label('期中考副參')
                            ->maxLength(255),
                        TextInput::make('final_reference_primary')
                            ->label('期末考正參')
                            ->maxLength(255),
                        TextInput::make('final_reference_secondary')
                            ->label('期末考副參')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }
}

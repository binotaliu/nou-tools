<?php

namespace App\Filament\Resources\Courses\Resources\Textbooks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TextbookForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('教科書資訊')
                    ->schema([
                        TextInput::make('term')
                            ->label('學期')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('book_title')
                            ->label('書名')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('edition')
                            ->label('版本')
                            ->maxLength(255),
                        TextInput::make('price_info')
                            ->label('價格或補充資訊')
                            ->maxLength(255),
                        TextInput::make('reference_url')
                            ->label('參考連結')
                            ->url()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}

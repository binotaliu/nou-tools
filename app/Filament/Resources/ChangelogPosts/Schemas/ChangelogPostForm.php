<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangelogPosts\Schemas;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ChangelogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('標題')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
                        'slug',
                        Str::slug($state ?? ''),
                    )),
                TextInput::make('slug')
                    ->label('網址代稱')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit'),
                MarkdownEditor::make('body')
                    ->label('內文')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangelogPosts\Schemas;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ChangelogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('標題')
                    ->required()
                    ->maxLength(255),
                // Titles are almost always Chinese, and Str::slug() drops
                // CJK text entirely (no ASCII transliteration for it), so
                // the slug can't be auto-derived — same reason article
                // filenames are chosen by hand rather than from their title.
                TextInput::make('slug')
                    ->label('網址代稱')
                    ->helperText('用於網址，請輸入英文（例如 new-feature-launch）。')
                    ->required()
                    ->maxLength(255)
                    ->alphaDash()
                    ->ascii()
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit'),
                MarkdownEditor::make('body')
                    ->label('內文')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}

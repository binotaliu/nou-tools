<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiPages\Schemas;

use App\Models\WikiPage;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Title, description, categories and body belong to revisions, not the
 * page: the Create/Edit pages publish a new revision from them on save.
 */
class WikiPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('條目')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('標題')
                            ->required()
                            ->maxLength((int) config('wiki.max_title_length')),
                        TextInput::make('slug')
                            ->label('網址代稱')
                            ->helperText('英文小寫、數字與連字號，例如 nou-exams-info。修改後舊網址會自動轉址。')
                            ->required()
                            ->maxLength(WikiPage::SLUG_MAX_LENGTH)
                            ->regex(WikiPage::SLUG_PATTERN)
                            ->notIn(WikiPage::RESERVED_SLUGS),
                        Textarea::make('description')
                            ->label('摘要')
                            ->rows(2)
                            ->maxLength(300)
                            ->columnSpanFull(),
                        TagsInput::make('categories')
                            ->label('分類')
                            ->required()
                            ->columnSpanFull(),
                        Toggle::make('is_protected')
                            ->label('受保護（只有管理員可以修改）'),
                    ]),
                MarkdownEditor::make('body')
                    ->label('內文')
                    ->helperText('語法請見 /wiki/guide/syntax。')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('edit_summary')
                    ->label('編輯摘要')
                    ->helperText('會顯示在編輯歷史中。')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }
}

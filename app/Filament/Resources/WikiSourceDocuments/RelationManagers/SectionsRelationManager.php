<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiSourceDocuments\RelationManagers;

use App\Models\WikiSourceDocument;
use App\Models\WikiSourceSection;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use Illuminate\Validation\Rules\Unique;

/**
 * Key and title can be corrected here; the text is read-only because every
 * fetch rewrites it from the source.
 *
 * @property WikiSourceDocument $ownerRecord
 */
class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return '段落';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('代稱')
                    ->helperText('跨學期對應同一段落用，重新擷取時會依原文重新產生。')
                    ->required()
                    ->maxLength(60)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('wiki_source_document_id', $this->ownerRecord->id)),
                TextInput::make('title')
                    ->label('標題')
                    ->required()
                    ->maxLength(255),
                Textarea::make('body')
                    ->label('內容')
                    ->rows(16)
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('代稱'),
                TextColumn::make('title')
                    ->label('標題')
                    ->limit(60),
                TextColumn::make('page_start')
                    ->label('頁')
                    ->formatStateUsing(fn (WikiSourceSection $record): string => $record->page_start === $record->page_end
                        ? (string) $record->page_start
                        : $record->page_start.'–'.$record->page_end),
                TextColumn::make('size')
                    ->label('大小')
                    ->state(fn (WikiSourceSection $record): string => Number::fileSize(strlen($record->body))),
            ])
            ->defaultSort('position')
            ->recordActions([
                EditAction::make()
                    ->using(function (WikiSourceSection $record, array $data): WikiSourceSection {
                        $record->key = $data['key'];
                        $record->title = $data['title'];
                        $record->saveOrFail();

                        return $record;
                    }),
            ]);
    }
}

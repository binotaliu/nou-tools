<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiSourceDocuments\Schemas;

use App\Enums\WikiSourceDocumentStatus;
use App\Models\WikiSourceDocument;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use NouTools\Domains\Wiki\Support\WikiSourceDomains;

/**
 * Series and term identify a document and are fixed once it exists; the
 * Create page queues the first fetch from them on save.
 */
class WikiSourceDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('來源文件')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('url')
                            ->label('網址')
                            ->helperText('PDF 或網頁，只接受這些網站：'.implode('、', WikiSourceDomains::all()).'。')
                            ->required()
                            ->url()
                            ->maxLength(2048)
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (! WikiSourceDomains::allows((string) $value)) {
                                    $fail('網址只接受這些網站：'.implode('、', WikiSourceDomains::all()).'。');
                                }
                            })
                            ->columnSpanFull(),
                        TextInput::make('title')
                            ->label('標題')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('series')
                            ->label('系列')
                            ->helperText('同一種文件在各學期共用的名稱，英文小寫與連字號，例如 course-selection-notes。')
                            ->required()
                            ->maxLength(WikiSourceDocument::SERIES_MAX_LENGTH)
                            ->regex(WikiSourceDocument::SERIES_PATTERN)
                            ->datalist(fn (): array => WikiSourceDocument::query()->distinct()->orderBy('series')->pluck('series')->all())
                            ->disabledOn('edit'),
                        Toggle::make('is_evergreen')
                            ->label('不分學期的常態文件')
                            ->helperText('例如學則。新版本以生效日期區分。')
                            ->live()
                            ->disabledOn('edit'),
                        TextInput::make('term')
                            ->label('學期')
                            ->helperText('例如 115-1。')
                            ->required(fn (Get $get): bool => ! $get('is_evergreen'))
                            ->regex(WikiSourceDocument::TERM_PATTERN)
                            ->visible(fn (Get $get): bool => ! $get('is_evergreen'))
                            ->disabledOn('edit'),
                        DatePicker::make('effective_from')
                            ->label('生效日期')
                            ->native(false),
                        DatePicker::make('effective_to')
                            ->label('失效日期')
                            ->helperText('填了之後，編輯器不再把它當作該系列的最新版本。')
                            ->native(false),
                    ]),
                Section::make('擷取狀態')
                    ->columnSpanFull()
                    ->columns(2)
                    ->visibleOn('edit')
                    ->schema([
                        TextEntry::make('status')
                            ->label('狀態')
                            ->badge()
                            ->formatStateUsing(fn (WikiSourceDocumentStatus $state): string => $state->label())
                            ->color(fn (WikiSourceDocumentStatus $state): string => $state->color()),
                        TextEntry::make('fetched_at')
                            ->label('最後擷取')
                            ->dateTime('Y-m-d H:i', timezone: 'Asia/Taipei')
                            ->placeholder('—'),
                        TextEntry::make('page_count')
                            ->label('頁數')
                            ->placeholder('—'),
                        TextEntry::make('failure_reason')
                            ->label('失敗原因')
                            ->color('danger')
                            ->placeholder('—'),
                    ]),
            ]);
    }
}

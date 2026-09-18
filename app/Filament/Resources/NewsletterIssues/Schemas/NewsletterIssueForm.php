<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterIssues\Schemas;

use App\Enums\NewsletterSection;
use App\Models\Announcement;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Date;
use NouTools\Domains\Newsletter\Actions\QueryNewsletterCandidateAnnouncements;
use NouTools\Domains\Newsletter\Schedule\NewsletterCadence;

class NewsletterIssueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('基本資訊')
                    ->schema([
                        DatePicker::make('publishes_on')
                            ->label('發刊日（週一）')
                            ->helperText('期號與各日期區間由發刊日推算，建立後不可更改。')
                            ->required()
                            ->disabledOn('edit')
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (! app(NewsletterCadence::class)->isIssueDate((string) $value)) {
                                    $fail('發刊日必須是雙週報的發刊週一。');
                                }
                            }),
                        TextInput::make('issue_key')
                            ->label('期號')
                            ->disabled()
                            ->dehydrated(false)
                            ->hiddenOn('create'),
                        TextInput::make('title')
                            ->label('標題')
                            ->placeholder('留空則使用「浣熊的空大雙週報 期號」')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Grid::make(4)
                            ->schema([
                                DatePicker::make('covers_from')->label('公告起')->required(),
                                DatePicker::make('covers_to')->label('公告迄')->required(),
                                DatePicker::make('highlights_from')->label('重點起')->required(),
                                DatePicker::make('highlights_to')->label('重點迄')->required(),
                            ])
                            ->columnSpanFull()
                            ->hiddenOn('create'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('本期重點事項')
                    ->schema([
                        MarkdownEditor::make('highlights_intro')
                            ->label('開場文字'),
                        Repeater::make('highlights_events')
                            ->label('校曆事件')
                            ->helperText('建立時自動擷取自校曆，可手動調整；「重新讀取校曆」會覆蓋此列表。')
                            ->schema([
                                DatePicker::make('start')->label('開始')->required(),
                                DatePicker::make('end')->label('結束')->required(),
                                TextInput::make('name')->label('名稱')->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->addActionLabel('新增事件'),
                    ])
                    ->columnSpanFull()
                    ->hiddenOn('create'),

                Section::make('消息')
                    ->description('空大新消息與各中心消息。選擇公告會自動帶入來源、連結與標題。')
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('position')
                            ->schema(self::itemSchema())
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => filled($state['headline'] ?? null)
                                ? (NewsletterSection::tryFrom((string) ($state['section'] ?? ''))?->label() ?? '').'｜'.$state['headline']
                                : null)
                            ->defaultItems(0)
                            ->addActionLabel('新增消息'),
                    ])
                    ->columnSpanFull()
                    ->hiddenOn('create'),

                Section::make('專欄')
                    ->description('浣熊站長的自言自語、同學投稿等自由欄位。')
                    ->schema([
                        Repeater::make('columns')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('position')
                            ->schema([
                                TextInput::make('title')
                                    ->label('欄名')
                                    ->required()
                                    ->maxLength(255)
                                    ->default('浣熊站長的自言自語'),
                                TextInput::make('author')
                                    ->label('作者')
                                    ->maxLength(255),
                                MarkdownEditor::make('body')
                                    ->label('內文')
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->defaultItems(0)
                            ->addActionLabel('新增專欄'),
                    ])
                    ->columnSpanFull()
                    ->hiddenOn('create'),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function itemSchema(): array
    {
        return [
            Select::make('section')
                ->label('欄位')
                ->options(NewsletterSection::getLabels())
                ->required()
                ->default(NewsletterSection::News->value),
            Select::make('announcement_id')
                ->label('來源公告')
                ->helperText('搜尋本期公告範圍內的公告標題或來源。')
                ->searchable()
                ->getSearchResultsUsing(fn (string $search, Get $get): array => self::searchCandidateAnnouncements($search, $get))
                ->getOptionLabelUsing(fn (mixed $value): ?string => ($announcement = Announcement::find($value)) !== null
                    ? self::announcementOptionLabel($announcement)
                    : null)
                ->live()
                ->afterStateUpdated(function (mixed $state, Set $set, Get $get): void {
                    $announcement = $state !== null ? Announcement::find($state) : null;

                    if ($announcement === null) {
                        return;
                    }

                    $set('source_name', $announcement->source_name);
                    $set('url', $announcement->url);

                    if (blank($get('headline'))) {
                        $set('headline', $announcement->title);
                    }
                }),
            TextInput::make('source_name')
                ->label('來源')
                ->required()
                ->maxLength(255),
            TextInput::make('url')
                ->label('原文連結')
                ->url()
                ->maxLength(2048),
            TextInput::make('headline')
                ->label('標題')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            MarkdownEditor::make('summary')
                ->label('摘要')
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function searchCandidateAnnouncements(string $search, Get $get): array
    {
        $from = $get('../../covers_from');
        $to = $get('../../covers_to');

        if (blank($from) || blank($to)) {
            return [];
        }

        return app(QueryNewsletterCandidateAnnouncements::class)(Date::parse($from), Date::parse($to))
            ->where(fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('source_name', 'like', "%{$search}%"))
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Announcement $announcement): array => [$announcement->id => self::announcementOptionLabel($announcement)])
            ->all();
    }

    private static function announcementOptionLabel(Announcement $announcement): string
    {
        $date = ($announcement->published_at ?? $announcement->fetched_at)?->format('m/d');

        return "[{$announcement->source_name}] {$announcement->title}".($date !== null ? "（{$date}）" : '');
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterIssues\Schemas;

use App\Enums\NewsletterSection;
use App\Models\Announcement;
use App\Models\NewsletterIssue;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Mansoor\UnsplashPicker\Actions\UnsplashPickerAction;
use NouTools\Domains\Newsletter\Actions\ListNewsletterCandidateAnnouncements;
use NouTools\Domains\Newsletter\Actions\QueryNewsletterCandidateAnnouncements;
use NouTools\Domains\Newsletter\Schedule\NewsletterCadence;
use NouTools\Domains\Shared\SchoolCalendar\Actions\ListSchoolEventsBetween;

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
                        FileUpload::make('cover_image')
                            ->label('封面圖片')
                            ->image()
                            ->disk('public')
                            ->directory('newsletter-covers')
                            // The Unsplash picker attaches the file via a browser event
                            // that FilePond picks up asynchronously, in a *separate* later
                            // Livewire request than the one the picker's own afterUpload()
                            // hook runs in, so setting the credit fields directly from
                            // afterUpload() gets clobbered by that later request's stale
                            // snapshot. Stash it in the session there and consume it here,
                            // in cover_image's own afterStateUpdated — which fires in that
                            // same later request that's already updating cover_image
                            // itself — so the credit rides along in the same response and
                            // dehydrates into $data normally at Save.
                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                if (blank($state)) {
                                    $set('cover_image_credit_name', null);
                                    $set('cover_image_credit_url', null);

                                    return;
                                }

                                $credit = Session::pull('newsletter_issue_pending_unsplash_credit');

                                if ($credit !== null) {
                                    $set('cover_image_credit_name', $credit['name']);
                                    $set('cover_image_credit_url', $credit['url']);
                                }
                            })
                            ->hintAction(
                                UnsplashPickerAction::make()
                                    ->afterUpload(function (array $data): void {
                                        $image = Arr::first(Arr::get($data, 'selectedImages', []));

                                        if ($image === null) {
                                            return;
                                        }

                                        Session::put('newsletter_issue_pending_unsplash_credit', [
                                            'name' => Arr::get($image, 'user.name'),
                                            'url' => Arr::get($image, 'user.links.html'),
                                        ]);
                                    })
                            )
                            ->columnSpanFull()
                            ->hiddenOn('create'),
                        Hidden::make('cover_image_credit_name'),
                        Hidden::make('cover_image_credit_url'),
                        Placeholder::make('cover_image_credit')
                            ->hiddenLabel()
                            ->visible(fn (Get $get): bool => filled($get('cover_image_credit_name')))
                            ->content(fn (Get $get): string => '圖片來源：'.$get('cover_image_credit_name').'（Unsplash）')
                            ->columnSpanFull()
                            ->hiddenOn('create'),
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

                Section::make('前言')
                    ->schema([
                        MarkdownEditor::make('highlights_intro')
                            ->hiddenLabel(),
                    ])
                    ->columnSpanFull()
                    ->hiddenOn('create'),

                Section::make('本期行事曆')
                    ->key('highlightsSection')
                    ->headerActions([self::importCalendarEventsAction()])
                    ->schema([
                        Repeater::make('highlights_events')
                            ->label('校曆事件')
                            ->helperText('建立時自動擷取自校曆，可手動調整；「匯入校曆事件」會覆蓋此列表。')
                            ->schema([
                                DatePicker::make('start')->label('開始')->required(),
                                DatePicker::make('end')->label('結束')->required(),
                                TextInput::make('name')->label('名稱')->required(),
                                TextInput::make('description')
                                    ->label('簡短說明')
                                    ->maxLength(120)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->addActionLabel('新增事件'),
                    ])
                    ->columnSpanFull()
                    ->hiddenOn('create'),

                Section::make('消息')
                    ->key('itemsSection')
                    ->headerActions([self::importAnnouncementsAction()])
                    ->description('空大新消息、藝文活動與各中心消息。選擇公告會自動帶入來源、連結與標題。')
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

    private static function importCalendarEventsAction(): Action
    {
        return Action::make('importCalendarEvents')
            ->label('匯入校曆事件')
            ->icon(Heroicon::CalendarDays)
            ->color('gray')
            ->hidden(fn (?NewsletterIssue $record): bool => $record?->isPublished() ?? false)
            ->requiresConfirmation()
            ->modalDescription('會以校曆中「重點起」至「重點迄」的事件覆蓋目前的校曆事件列表，儲存後才會生效。')
            ->action(function (Get $get, Set $set): void {
                $from = $get('highlights_from');
                $to = $get('highlights_to');

                if (blank($from) || blank($to)) {
                    Notification::make()->warning()->title('請先設定重點起訖日期')->send();

                    return;
                }

                $events = app(ListSchoolEventsBetween::class)(
                    Date::parse($from)->toDateString(),
                    Date::parse($to)->toDateString(),
                );

                $set('highlights_events', $events);

                Notification::make()->success()->title('已匯入 '.count($events).' 個校曆事件')->body('請記得儲存。')->send();
            });
    }

    private static function importAnnouncementsAction(): Action
    {
        return Action::make('importAnnouncements')
            ->label('匯入公告')
            ->icon(Heroicon::ArrowDownTray)
            ->color('gray')
            ->hidden(fn (?NewsletterIssue $record): bool => $record?->isPublished() ?? false)
            ->modalHeading('匯入公告')
            ->modalDescription('列出「公告起」至「公告迄」範圍內、尚未加入的公告。匯入後請記得儲存。')
            ->modalSubmitActionLabel('匯入')
            ->schema(fn (Get $get): array => self::announcementImportFields($get))
            ->action(function (array $data, Get $get, Set $set): void {
                $ids = collect(NewsletterSection::cases())
                    ->flatMap(fn (NewsletterSection $section): array => $data[$section->value] ?? [])
                    ->unique()
                    ->values()
                    ->all();

                if ($ids === []) {
                    return;
                }

                $announcements = Announcement::query()->whereKey($ids)->get()->keyBy('id');
                $items = $get('items') ?? [];

                $importedIds = [];

                foreach (NewsletterSection::cases() as $section) {
                    foreach ($data[$section->value] ?? [] as $id) {
                        $announcement = $announcements->get($id);

                        // 藝文活動 and 空大新消息 list the same announcements; 空大新消息 takes precedence.
                        if ($announcement === null || in_array($id, $importedIds, true)) {
                            continue;
                        }

                        $importedIds[] = $id;

                        $items[(string) Str::uuid()] = [
                            'section' => $section->value,
                            'announcement_id' => $announcement->id,
                            'source_name' => $announcement->source_name,
                            'url' => $announcement->url,
                            'headline' => Str::limit($announcement->title, 250, ''),
                            'summary' => null,
                        ];
                    }
                }

                $set('items', $items);

                Notification::make()->success()->title('已匯入 '.count($ids).' 則公告')->body('請記得儲存。')->send();
            });
    }

    /**
     * One checkbox list per newsletter section, listing the candidate
     * announcements that aren't already items of the issue.
     *
     * @return array<int, mixed>
     */
    private static function announcementImportFields(Get $get): array
    {
        $from = $get('covers_from');
        $to = $get('covers_to');

        if (blank($from) || blank($to)) {
            return [Placeholder::make('missing_window')->hiddenLabel()->content('請先設定公告起訖日期。')];
        }

        $imported = collect($get('items') ?? [])->pluck('announcement_id')->filter()->map(fn (mixed $id): int => (int) $id)->all();
        $candidates = app(ListNewsletterCandidateAnnouncements::class)(Date::parse($from), Date::parse($to));

        $fields = $candidates
            ->map(fn ($announcements, string $section) => $announcements->reject(fn (Announcement $announcement): bool => in_array($announcement->id, $imported, true)))
            ->filter(fn ($announcements): bool => $announcements->isNotEmpty())
            ->map(fn ($announcements, string $section): CheckboxList => CheckboxList::make($section)
                ->label(NewsletterSection::from($section)->label())
                ->options($announcements->mapWithKeys(fn (Announcement $announcement): array => [$announcement->id => self::announcementOptionLabel($announcement)])->all())
                ->searchable()
                ->bulkToggleable()
                ->columns(1))
            ->values()
            ->all();

        return $fields !== [] ? $fields : [Placeholder::make('no_candidates')->hiddenLabel()->content('沒有可匯入的公告。')];
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

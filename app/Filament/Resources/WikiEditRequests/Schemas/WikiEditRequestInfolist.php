<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiEditRequests\Schemas;

use App\Enums\WikiEditRequestStatus;
use App\Models\WikiEditRequest;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use NouTools\Domains\Wiki\Actions\RenderWikiMarkdown;
use NouTools\Domains\Wiki\ViewModels\WikiDiffLineViewModel;

class WikiEditRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('請求')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')
                            ->label('狀態')
                            ->badge()
                            ->formatStateUsing(fn (WikiEditRequestStatus $state): string => $state->label())
                            ->color(fn (WikiEditRequestStatus $state): string => $state->color()),
                        TextEntry::make('target')
                            ->label('條目')
                            ->state(fn (WikiEditRequest $record): string => $record->page !== null
                                ? $record->page->slug
                                : '新條目（建議代稱：'.($record->draft_slug ?? $record->requested_slug ?? '—').'）'),
                        TextEntry::make('instruction')
                            ->label('修改說明')
                            ->columnSpanFull(),
                        TextEntry::make('refinements')
                            ->label('再調整')
                            ->listWithLineBreaks()
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('source_urls')
                            ->label('來源網址')
                            ->listWithLineBreaks()
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab()
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('studentSchedule.blocked')
                            ->label('提出的裝置')
                            ->state(fn (WikiEditRequest $record): string => $record->studentSchedule === null
                                ? '—'
                                : '課表 #'.$record->student_schedule_id.($record->studentSchedule->wiki_blocked_at !== null ? '（已封鎖）' : '')),
                        TextEntry::make('review_note')
                            ->label('審核備註')
                            ->placeholder('—'),
                    ]),
                Section::make('AI 評估')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('risk')
                            ->label('風險')
                            ->badge()
                            ->state(fn (WikiEditRequest $record): ?string => $record->risk()?->label())
                            ->color(fn (WikiEditRequest $record): string => $record->risk()?->color() ?? 'gray')
                            ->placeholder('—'),
                        TextEntry::make('ai_verdict.reviewReasons')
                            ->label('需要審核的原因')
                            ->listWithLineBreaks()
                            ->placeholder('—'),
                        TextEntry::make('ai_verdict.riskReasons')
                            ->label('風險說明')
                            ->listWithLineBreaks()
                            ->placeholder('—'),
                        TextEntry::make('ai_verdict.sourcesUsed')
                            ->label('AI 讀過的來源')
                            ->listWithLineBreaks()
                            ->placeholder('—'),
                        TextEntry::make('ai_verdict.declineReason')
                            ->label('未採納原因')
                            ->placeholder('—'),
                        TextEntry::make('ai_verdict.problems')
                            ->label('驗證問題')
                            ->listWithLineBreaks()
                            ->placeholder('—'),
                    ]),
                Section::make('草稿')
                    ->columnSpanFull()
                    ->columns(2)
                    ->visible(fn (WikiEditRequest $record): bool => filled($record->draft_body))
                    ->schema([
                        TextEntry::make('draft_title')->label('標題'),
                        TextEntry::make('draft_edit_summary')->label('編輯摘要'),
                        TextEntry::make('draft_description')->label('摘要')->placeholder('—'),
                        TextEntry::make('draft_categories')->label('分類')->badge()->placeholder('—'),
                        ViewEntry::make('diff')
                            ->label('差異')
                            ->view('filament.wiki.diff')
                            ->state(fn (WikiEditRequest $record): array => WikiDiffLineViewModel::between(
                                (string) $record->baseRevision?->body,
                                (string) $record->draft_body,
                            ))
                            ->columnSpanFull(),
                        ViewEntry::make('preview')
                            ->label('預覽')
                            ->view('filament.wiki.preview')
                            ->state(fn (WikiEditRequest $record): string => app(RenderWikiMarkdown::class)($record->draft_body))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiSourceDocuments\Tables;

use App\Enums\WikiSourceDocumentStatus;
use App\Models\WikiSourceDocument;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use NouTools\Domains\Wiki\Actions\QueueWikiSourceDocumentFetch;

class WikiSourceDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->formatStateUsing(fn (WikiSourceDocumentStatus $state): string => $state->label())
                    ->color(fn (WikiSourceDocumentStatus $state): string => $state->color()),
                TextColumn::make('series')
                    ->label('系列')
                    ->searchable(),
                TextColumn::make('term')
                    ->label('學期')
                    ->placeholder('常態'),
                TextColumn::make('title')
                    ->label('標題')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('sections_count')
                    ->label('段落數')
                    ->counts('sections'),
                TextColumn::make('fetched_at')
                    ->label('最後擷取')
                    ->dateTime('Y-m-d H:i', timezone: 'Asia/Taipei')
                    ->placeholder('—'),
            ])
            ->defaultSort('updated_at', 'desc')
            // A queued fetch finishes while the admin watches the list.
            ->poll('10s')
            ->filters([
                SelectFilter::make('series')
                    ->label('系列')
                    ->options(fn (): array => WikiSourceDocument::query()->distinct()->orderBy('series')->pluck('series', 'series')->all()),
                SelectFilter::make('status')
                    ->label('狀態')
                    ->options(WikiSourceDocumentStatus::class),
            ])
            ->recordActions([
                Action::make('refetch')
                    ->label('重新擷取')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->visible(fn (WikiSourceDocument $record): bool => $record->status !== WikiSourceDocumentStatus::Fetching)
                    ->action(function (WikiSourceDocument $record): void {
                        app(QueueWikiSourceDocumentFetch::class)($record);

                        Notification::make()->success()->title('已加入擷取佇列')->send();
                    }),
                EditAction::make(),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiPages\RelationManagers;

use App\Enums\WikiRevisionOrigin;
use App\Models\WikiPage;
use App\Models\WikiRevision;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use NouTools\Domains\Wiki\Actions\PublishWikiRevision;
use NouTools\Domains\Wiki\DataTransferObjects\PublishWikiRevisionDTO;

/**
 * @property WikiPage $ownerRecord
 */
class RevisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'revisions';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return '版本紀錄';
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#'),
                TextColumn::make('edit_summary')
                    ->label('編輯摘要')
                    ->placeholder('—')
                    ->limit(60),
                TextColumn::make('origin')
                    ->label('來源')
                    ->badge()
                    ->formatStateUsing(fn (WikiRevisionOrigin $state): string => $state->label()),
                TextColumn::make('user.name')
                    ->label('管理員')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('時間')
                    ->dateTime('Y-m-d H:i', timezone: 'Asia/Taipei'),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                Action::make('view')
                    ->label('檢視')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (WikiRevision $record): string => route('wiki.revision', ['slug' => $this->ownerRecord->slug, 'revision' => $record->id]))
                    ->openUrlInNewTab(),
                Action::make('revert')
                    ->label('還原到這個版本')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->visible(fn (WikiRevision $record): bool => $record->id !== $this->ownerRecord->current_revision_id)
                    ->action(function (WikiRevision $record): void {
                        app(PublishWikiRevision::class)($this->ownerRecord, new PublishWikiRevisionDTO(
                            title: $record->title,
                            description: $record->description,
                            body: $record->body,
                            categories: $record->categories,
                            editSummary: "還原到版本 #{$record->id}",
                            origin: WikiRevisionOrigin::Revert,
                            userId: auth()->id(),
                        ));

                        Notification::make()->success()->title('已還原')->send();
                    }),
            ]);
    }
}

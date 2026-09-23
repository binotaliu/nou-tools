<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangelogPosts\Pages;

use App\Filament\Resources\ChangelogPosts\ChangelogPostResource;
use App\Models\ChangelogPost;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use NouTools\Domains\Changelog\Actions\PublishChangelogPost;

/**
 * @property ChangelogPost $record
 */
class EditChangelogPost extends EditRecord
{
    protected static string $resource = ChangelogPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getPreviewAction(),
            $this->getPublishAction(),
            DeleteAction::make(),
        ];
    }

    private function getPreviewAction(): Action
    {
        return Action::make('preview')
            ->label('預覽')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->url(fn (ChangelogPost $record): string => route('changelog.show', $record))
            ->openUrlInNewTab();
    }

    private function getPublishAction(): Action
    {
        return Action::make('publishNow')
            ->label('立即發布')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('立即公開這篇更新日誌，並出現在列表中。請先儲存所有修改。')
            ->visible(fn (ChangelogPost $record): bool => ! $record->isPublished())
            ->action(function (ChangelogPost $record): void {
                try {
                    app(PublishChangelogPost::class)($record);
                } catch (DomainException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('已發布')->send();
            });
    }
}

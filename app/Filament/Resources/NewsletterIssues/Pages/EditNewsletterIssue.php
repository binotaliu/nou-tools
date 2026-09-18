<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterIssues\Pages;

use App\Enums\NewsletterIssueStatus;
use App\Filament\Resources\NewsletterIssues\NewsletterIssueResource;
use App\Jobs\DraftNewsletterIssueWithAi;
use App\Models\NewsletterIssue;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use NouTools\Domains\Newsletter\Actions\ChangeNewsletterIssueReadiness;
use NouTools\Domains\Newsletter\Actions\PublishNewsletterIssue;
use NouTools\Domains\Newsletter\Actions\RefreshNewsletterHighlightEvents;

/**
 * @property NewsletterIssue $record
 */
class EditNewsletterIssue extends EditRecord
{
    protected static string $resource = NewsletterIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getPreviewAction(),
            $this->getAiDraftAction(),
            $this->getRefreshCalendarAction(),
            $this->getMarkReadyAction(),
            $this->getReturnToDraftAction(),
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
            ->url(fn (NewsletterIssue $record): string => route('newsletter.show', $record->issue_key))
            ->openUrlInNewTab();
    }

    private function getAiDraftAction(): Action
    {
        return Action::make('aiDraft')
            ->label('以 AI 產生草稿')
            ->icon('heroicon-o-sparkles')
            ->color('info')
            ->requiresConfirmation()
            ->modalDescription(fn (NewsletterIssue $record): string => $record->items()->exists()
                ? '這會以 AI 重新挑選公告並取代目前所有消息與開場文字（專欄不受影響）。未儲存的修改會遺失。'
                : 'AI 會挑選本期公告範圍內的消息並撰寫開場文字。')
            ->visible(fn (NewsletterIssue $record): bool => ! $record->isPublished())
            ->action(function (NewsletterIssue $record): void {
                DraftNewsletterIssueWithAi::dispatchAfterResponse($record);

                Notification::make()
                    ->info()
                    ->title('AI 正在產生草稿')
                    ->body('約需一至兩分鐘，完成後重新整理頁面即可看到結果。')
                    ->send();
            });
    }

    private function getRefreshCalendarAction(): Action
    {
        return Action::make('refreshCalendar')
            ->label('重新讀取校曆')
            ->icon('heroicon-o-calendar-days')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('會以目前的校曆覆蓋本期的校曆事件列表。')
            ->visible(fn (NewsletterIssue $record): bool => ! $record->isPublished())
            ->action(function (NewsletterIssue $record): void {
                app(RefreshNewsletterHighlightEvents::class)($record);

                $this->refreshFormData(['highlights_events']);

                Notification::make()->success()->title('已重新讀取校曆')->send();
            });
    }

    private function getMarkReadyAction(): Action
    {
        return Action::make('markReady')
            ->label('標記為待發布')
            ->icon('heroicon-o-check-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(fn (NewsletterIssue $record): string => "{$record->publishes_on->toDateString()} 早上 8 點會自動發布。請先儲存所有修改。")
            ->visible(fn (NewsletterIssue $record): bool => $record->status === NewsletterIssueStatus::Draft)
            ->action(function (NewsletterIssue $record): void {
                app(ChangeNewsletterIssueReadiness::class)($record, true);

                Notification::make()->success()->title('已標記為待發布')->send();
            });
    }

    private function getReturnToDraftAction(): Action
    {
        return Action::make('returnToDraft')
            ->label('退回草稿')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->visible(fn (NewsletterIssue $record): bool => $record->status === NewsletterIssueStatus::Ready)
            ->action(function (NewsletterIssue $record): void {
                app(ChangeNewsletterIssueReadiness::class)($record, false);

                Notification::make()->success()->title('已退回草稿')->send();
            });
    }

    private function getPublishAction(): Action
    {
        return Action::make('publishNow')
            ->label('立即發布')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('立即公開這一期，並出現在列表與 Atom 訂閱中。請先儲存所有修改。')
            ->visible(fn (NewsletterIssue $record): bool => ! $record->isPublished())
            ->action(function (NewsletterIssue $record): void {
                try {
                    app(PublishNewsletterIssue::class)($record);
                } catch (DomainException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('已發布')->send();
            });
    }
}

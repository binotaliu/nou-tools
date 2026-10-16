<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiEditRequests\Pages;

use App\Enums\WikiEditRequestStatus;
use App\Filament\Resources\WikiEditRequests\WikiEditRequestResource;
use App\Models\User;
use App\Models\WikiEditRequest;
use App\Models\WikiPage;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use NouTools\Domains\Wiki\Actions\ApproveWikiEditRequest;
use NouTools\Domains\Wiki\Actions\RejectWikiEditRequest;

/**
 * @property WikiEditRequest $record
 */
class ViewWikiEditRequest extends ViewRecord
{
    protected static string $resource = WikiEditRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getApproveAction(),
            $this->getRejectAction(),
            $this->getBlockAction(),
            $this->getUnblockAction(),
        ];
    }

    private function getApproveAction(): Action
    {
        return Action::make('approve')
            ->label('核准並發布')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (WikiEditRequest $record): bool => $record->status === WikiEditRequestStatus::PendingReview)
            ->schema(fn (WikiEditRequest $record): array => $record->isNewPage() ? [
                TextInput::make('slug')
                    ->label('網址代稱')
                    ->default($record->draft_slug)
                    ->required()
                    ->maxLength(WikiPage::SLUG_MAX_LENGTH)
                    ->regex(WikiPage::SLUG_PATTERN)
                    ->notIn(WikiPage::RESERVED_SLUGS),
            ] : [])
            ->action(function (WikiEditRequest $record, array $data): void {
                /** @var User $reviewer */
                $reviewer = auth()->user();

                try {
                    app(ApproveWikiEditRequest::class)($record, $reviewer, $data['slug'] ?? null);
                } catch (DomainException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('已發布')->send();
                $this->refreshFormData(['status']);
            });
    }

    private function getRejectAction(): Action
    {
        return Action::make('reject')
            ->label('退回')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (WikiEditRequest $record): bool => $record->status === WikiEditRequestStatus::PendingReview)
            ->schema([
                Textarea::make('note')
                    ->label('退回原因（會顯示給提出者）')
                    ->rows(3)
                    ->maxLength(1000),
            ])
            ->action(function (WikiEditRequest $record, array $data): void {
                /** @var User $reviewer */
                $reviewer = auth()->user();

                try {
                    app(RejectWikiEditRequest::class)($record, $reviewer, $data['note'] ?? null);
                } catch (DomainException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('已退回')->send();
                $this->refreshFormData(['status', 'review_note']);
            });
    }

    private function getBlockAction(): Action
    {
        return Action::make('block')
            ->label('封鎖此裝置')
            ->icon('heroicon-o-no-symbol')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription('這個課表登入的所有裝置都將無法再提出知識庫修改。')
            ->visible(fn (WikiEditRequest $record): bool => $record->studentSchedule !== null && $record->studentSchedule->wiki_blocked_at === null)
            ->action(function (WikiEditRequest $record): void {
                $schedule = $record->studentSchedule;
                $schedule->wiki_blocked_at = now();
                $schedule->save();

                Notification::make()->success()->title('已封鎖')->send();
            });
    }

    private function getUnblockAction(): Action
    {
        return Action::make('unblock')
            ->label('解除封鎖')
            ->icon('heroicon-o-lock-open')
            ->color('gray')
            ->visible(fn (WikiEditRequest $record): bool => $record->studentSchedule?->wiki_blocked_at !== null)
            ->action(function (WikiEditRequest $record): void {
                $schedule = $record->studentSchedule;
                $schedule->wiki_blocked_at = null;
                $schedule->save();

                Notification::make()->success()->title('已解除封鎖')->send();
            });
    }
}

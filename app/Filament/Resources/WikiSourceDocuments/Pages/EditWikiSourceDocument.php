<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiSourceDocuments\Pages;

use App\Enums\WikiSourceDocumentStatus;
use App\Filament\Resources\WikiSourceDocuments\WikiSourceDocumentResource;
use App\Models\WikiSourceDocument;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use NouTools\Domains\Wiki\Actions\QueueWikiSourceDocumentFetch;

/**
 * Saving changes the url, title and dates only (series and term are fixed);
 * 重新擷取 queues a fresh fetch, which replaces the stored sections.
 *
 * @property WikiSourceDocument $record
 */
class EditWikiSourceDocument extends EditRecord
{
    protected static string $resource = WikiSourceDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refetch')
                ->label('重新擷取')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->visible(fn (WikiSourceDocument $record): bool => $record->status !== WikiSourceDocumentStatus::Fetching)
                ->action(function (WikiSourceDocument $record): void {
                    app(QueueWikiSourceDocumentFetch::class)($record);

                    $this->refreshFormData(['status', 'failure_reason']);

                    Notification::make()->success()->title('已加入擷取佇列')->body('完成後重新整理頁面即可看到結果。')->send();
                }),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [...$data, 'is_evergreen' => $data['term'] === null];
    }

    /**
     * @param  WikiSourceDocument  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->url = $data['url'];
        $record->title = $data['title'];
        $record->effective_from = $data['effective_from'] ?? null;
        $record->effective_to = $data['effective_to'] ?? null;
        $record->saveOrFail();

        return $record;
    }
}

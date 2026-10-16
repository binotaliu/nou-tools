<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiPages\Pages;

use App\Enums\WikiRevisionOrigin;
use App\Filament\Resources\WikiPages\WikiPageResource;
use App\Models\WikiPage;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use NouTools\Domains\Wiki\Actions\PublishWikiRevision;
use NouTools\Domains\Wiki\DataTransferObjects\PublishWikiRevisionDTO;

/**
 * Saving publishes a new `admin` revision whenever the content or the slug
 * changed; toggling protection or 精選 alone doesn't add one.
 *
 * @property WikiPage $record
 */
class EditWikiPage extends EditRecord
{
    protected static string $resource = WikiPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label('檢視')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (WikiPage $record): string => route('wiki.show', $record->slug))
                ->openUrlInNewTab()
                ->visible(fn (WikiPage $record): bool => $record->current_revision_id !== null && ! $record->trashed()),
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $revision = $this->record->currentRevision;

        return [
            ...$data,
            'title' => $revision?->title,
            'description' => $revision?->description,
            'categories' => $revision->categories ?? [],
            'body' => $revision?->body,
            'edit_summary' => null,
        ];
    }

    /**
     * @param  WikiPage  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $revision = $record->currentRevision;
        $categories = array_values($data['categories'] ?? []);

        $changed = $revision === null
            || $data['slug'] !== $record->slug
            || $data['title'] !== $revision->title
            || ($data['description'] ?? null) !== $revision->description
            || $categories !== $revision->categories
            || rtrim((string) $data['body']) !== rtrim($revision->body);

        if ($changed) {
            try {
                app(PublishWikiRevision::class)($record, new PublishWikiRevisionDTO(
                    title: $data['title'],
                    description: $data['description'] ?? null,
                    body: $data['body'],
                    categories: $categories,
                    editSummary: filled($data['edit_summary'] ?? null) ? $data['edit_summary'] : '管理員編輯',
                    origin: WikiRevisionOrigin::Admin,
                    userId: auth()->id(),
                ), $data['slug']);
            } catch (DomainException $exception) {
                throw ValidationException::withMessages(['data.slug' => $exception->getMessage()]);
            }
        }

        $record->refresh();
        $record->is_protected = (bool) ($data['is_protected'] ?? false);
        $record->is_featured = (bool) ($data['is_featured'] ?? false);
        $record->save();

        return $record;
    }
}

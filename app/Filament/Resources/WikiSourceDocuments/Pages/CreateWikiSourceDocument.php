<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiSourceDocuments\Pages;

use App\Filament\Resources\WikiSourceDocuments\WikiSourceDocumentResource;
use DomainException;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use NouTools\Domains\Wiki\Actions\CreateWikiSourceDocument as CreateWikiSourceDocumentAction;
use NouTools\Domains\Wiki\DataTransferObjects\FetchWikiSourceDocumentDTO;

/**
 * Saving registers the document and queues its first fetch.
 */
class CreateWikiSourceDocument extends CreateRecord
{
    protected static string $resource = WikiSourceDocumentResource::class;

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Halt
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(CreateWikiSourceDocumentAction::class)(new FetchWikiSourceDocumentDTO(
                url: $data['url'],
                series: $data['series'],
                term: ($data['is_evergreen'] ?? false) ? null : $data['term'],
                title: $data['title'],
            ));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['data.series' => $exception->getMessage()]);
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '已加入擷取佇列';
    }
}

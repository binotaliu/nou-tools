<?php

declare(strict_types=1);

namespace App\Filament\Resources\WikiPages\Pages;

use App\Enums\WikiRevisionOrigin;
use App\Filament\Resources\WikiPages\WikiPageResource;
use App\Models\WikiPage;
use DomainException;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use NouTools\Domains\Wiki\Actions\PublishWikiRevision;
use NouTools\Domains\Wiki\DataTransferObjects\PublishWikiRevisionDTO;

class CreateWikiPage extends CreateRecord
{
    protected static string $resource = WikiPageResource::class;

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Halt
     */
    protected function handleRecordCreation(array $data): Model
    {
        try {
            $revision = app(PublishWikiRevision::class)(null, new PublishWikiRevisionDTO(
                title: $data['title'],
                description: $data['description'] ?? null,
                body: $data['body'],
                categories: array_values($data['categories'] ?? []),
                editSummary: filled($data['edit_summary'] ?? null) ? $data['edit_summary'] : '建立條目',
                origin: WikiRevisionOrigin::Admin,
                userId: auth()->id(),
            ), $data['slug']);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['data.slug' => $exception->getMessage()]);
        }

        $page = WikiPage::query()->findOrFail($revision->wiki_page_id);
        $page->is_protected = (bool) ($data['is_protected'] ?? false);
        $page->is_featured = (bool) ($data['is_featured'] ?? false);
        $page->save();

        return $page;
    }
}

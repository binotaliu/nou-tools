<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Actions;

use App\Models\WikiEditRequest;
use Illuminate\Http\UploadedFile;

/**
 * Puts a visitor's uploaded screenshots on the private wiki_screenshots
 * disk under random names, keeping the original name only for display.
 */
final readonly class StoreWikiScreenshots
{
    /**
     * @param  list<UploadedFile>  $files
     * @return list<array{path: string, name: string}>
     */
    public function __invoke(array $files): array
    {
        return array_map(fn (UploadedFile $file): array => [
            'path' => (string) $file->store('', ['disk' => WikiEditRequest::SCREENSHOT_DISK]),
            'name' => mb_substr($file->getClientOriginalName(), 0, 100),
        ], $files);
    }
}

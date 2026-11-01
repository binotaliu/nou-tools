<?php

declare(strict_types=1);

namespace NouTools\Domains\Cafe\Actions;

use App\Models\CafeStickyNote;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Keeps a drawing on the private disk under a random name; the original file
 * name is never used.
 */
final readonly class StoreStickyNoteDrawing
{
    public function __invoke(UploadedFile $drawing): string
    {
        $path = Str::random(40).'.png';

        Storage::disk(CafeStickyNote::DRAWING_DISK)->putFileAs('', $drawing, $path);

        return $path;
    }
}

<?php

declare(strict_types=1);

namespace NouTools\Domains\Schedules\Actions;

use Illuminate\Support\Facades\Cache;
use NouTools\Domains\Shared\Pdf\HtmlToPdf;

final readonly class RenderSchedulePdf
{
    public function __construct(private HtmlToPdf $htmlToPdf) {}

    /**
     * Launching Chromium takes seconds, so an unchanged sheet is served from
     * the cache, keyed by its HTML. The PDF is stored base64-encoded because
     * the default database cache store can't hold raw bytes.
     */
    public function __invoke(string $html): string
    {
        $encoded = Cache::remember(
            'schedule-print-pdf:'.sha1($html),
            now()->addDay(),
            fn (): string => base64_encode($this->htmlToPdf->landscapeA4($html)),
        );

        return base64_decode($encoded, true) ?: '';
    }
}

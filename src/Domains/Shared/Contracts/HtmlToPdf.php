<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Contracts;

interface HtmlToPdf
{
    /**
     * Render a self-contained HTML document as an A4 landscape PDF.
     *
     * @return string The raw PDF bytes.
     */
    public function landscapeA4(string $html): string;
}

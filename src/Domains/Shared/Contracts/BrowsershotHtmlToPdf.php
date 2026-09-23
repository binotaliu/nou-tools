<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Contracts;

use Spatie\Browsershot\Browsershot;

/**
 * Shares Chromium's binary paths with the og:image screenshots
 * (`LARAVEL_SCREENSHOT_*` env vars), so there is one place to configure it.
 */
final readonly class BrowsershotHtmlToPdf implements HtmlToPdf
{
    public function landscapeA4(string $html): string
    {
        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->landscape()
            ->margins(0, 0, 0, 0)
            ->showBackground()
            ->waitUntilNetworkIdle()
            // The sheet is meant to clip at 210mm (`overflow-hidden` on
            // <main>), but Chromium's print pagination ignores CSS overflow
            // clipping and still emits overflow content onto a second PDF
            // page. Restricting the output to page 1 makes it actually clip.
            ->pages('1');

        /** @var array<string, mixed> $config */
        $config = config('laravel-screenshot.browsershot', []);

        if ($config['node_binary'] ?? null) {
            $browsershot->setNodeBinary($config['node_binary']);
        }

        if ($config['npm_binary'] ?? null) {
            $browsershot->setNpmBinary($config['npm_binary']);
        }

        if ($config['include_path'] ?? null) {
            $browsershot->setIncludePath($config['include_path']);
        }

        if ($config['chrome_path'] ?? null) {
            $browsershot->setChromePath($config['chrome_path']);
        }

        if ($config['node_modules_path'] ?? null) {
            $browsershot->setNodeModulePath($config['node_modules_path']);
        }

        if ($config['bin_path'] ?? null) {
            $browsershot->setBinPath($config['bin_path']);
        }

        if ($config['temp_path'] ?? null) {
            $browsershot->setCustomTempPath($config['temp_path']);
        }

        if ($config['no_sandbox'] ?? false) {
            $browsershot->noSandbox();
        }

        return $browsershot->pdf();
    }
}

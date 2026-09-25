<?php

declare(strict_types=1);

namespace NouTools\Domains\Announcements\Actions;

final readonly class ResolveAnnouncementUrl
{
    /**
     * Resolve a scraped href against the source's base URL.
     *
     * Absolute URLs pass through and root-relative ones (`/upload/x.pdf`) hang
     * off the base's origin, as a browser would resolve them: the sub-sites
     * live under a path (`https://www2.nou.edu.tw/coach`) yet link their
     * attachments from the host root, so appending to the whole base 404s.
     */
    public function __invoke(string $href, string $baseUrl): string
    {
        if (str_starts_with($href, 'http')) {
            return $href;
        }

        if (str_starts_with($href, '/')) {
            $parts = parse_url($baseUrl);

            if (isset($parts['scheme'], $parts['host'])) {
                $port = isset($parts['port']) ? ':'.$parts['port'] : '';

                return $parts['scheme'].'://'.$parts['host'].$port.$href;
            }
        }

        return rtrim($baseUrl, '/').'/'.ltrim($href, '/');
    }
}

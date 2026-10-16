<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Support;

/**
 * The hosts a submitter may cite and the editor may fetch
 * (`config('wiki.source_domains')`, subdomains included).
 */
final class WikiSourceDomains
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_values(array_map('strtolower', (array) config('wiki.source_domains')));
    }

    public static function allows(string $url): bool
    {
        if (preg_match('#^https?://#i', $url) !== 1) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower(rtrim($host, '.'));

        foreach (self::all() as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }
}

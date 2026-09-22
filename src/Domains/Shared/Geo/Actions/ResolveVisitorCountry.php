<?php

declare(strict_types=1);

namespace NouTools\Domains\Shared\Geo\Actions;

use Illuminate\Http\Request;

/**
 * Reads the visitor's country from Cloudflare's `CF-IPCountry` header. Not
 * analytics-specific: it's a generic Cloudflare-geo primitive that any
 * geo-gated feature can reuse.
 */
final class ResolveVisitorCountry
{
    public function __invoke(Request $request): ?string
    {
        $country = strtoupper(trim((string) $request->header('CF-IPCountry')));

        // 'XX' is Cloudflare's "couldn't determine" sentinel, 'T1' means Tor.
        // Both are as good as no header at all.
        if ($country === '' || $country === 'XX' || $country === 'T1') {
            return null;
        }

        return $country;
    }
}

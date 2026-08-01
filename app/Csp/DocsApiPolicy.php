<?php

declare(strict_types=1);

namespace App\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Spatie\Csp\Presets\Basic;

/**
 * CSP for the /docs/api Redoc page only — adds cdn.redoc.ly to script-src so
 * the rest of the site doesn't need to trust that host.
 */
final class DocsApiPolicy implements Preset
{
    public function configure(Policy $policy): void
    {
        (new Basic)->configure($policy);

        $policy->add(Directive::SCRIPT, 'cdn.redoc.ly');
    }
}

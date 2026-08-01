<?php

declare(strict_types=1);

namespace App\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Spatie\Csp\Presets\Basic;
use Spatie\Csp\Presets\CloudflareTurnstile;
use Spatie\Csp\Presets\GoogleAnalytics;
use Spatie\Csp\Presets\GoogleTagManager;

final class PublicSitePolicy implements Preset
{
    public function configure(Policy $policy): void
    {
        (new Basic)->configure($policy);
        (new GoogleTagManager)->configure($policy);
        (new GoogleAnalytics)->configure($policy);
        (new CloudflareTurnstile)->configure($policy);

        $policy->add(Directive::IMG, '*.tile.openstreetmap.org');
    }
}

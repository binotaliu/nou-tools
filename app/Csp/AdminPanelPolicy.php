<?php

declare(strict_types=1);

namespace App\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Spatie\Csp\Presets\Basic;

/**
 * Relaxed CSP for the Filament admin panel (/admin) only. Livewire v4's core
 * evaluator uses `new Function(...)`, and the published filament-map-picker
 * vendor view uses an async arrow-function x-init — both need 'unsafe-eval',
 * which the public-site policy (see PublicSitePolicy) intentionally omits.
 */
final class AdminPanelPolicy implements Preset
{
    public function configure(Policy $policy): void
    {
        (new Basic)->configure($policy);

        $policy
            ->add(Directive::SCRIPT, [Keyword::UNSAFE_EVAL, Keyword::UNSAFE_INLINE])
            ->add(Directive::STYLE, Keyword::UNSAFE_INLINE)
            ->add(Directive::IMG, ['data:', '*.tile.openstreetmap.org']);
    }
}

<?php

declare(strict_types=1);

namespace App\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

/**
 * Relaxed CSP for the Filament admin panel (/admin) only. Livewire v4's core
 * evaluator uses `new Function(...)`, and the published filament-map-picker
 * vendor view uses an async arrow-function x-init — both need 'unsafe-eval',
 * which the public-site policy (see PublicSitePolicy) intentionally omits.
 *
 * This does NOT extend Spatie\Csp\Presets\Basic and does NOT call
 * Policy::addNonce(): Basic's addNonce() appends a per-request nonce to
 * script-src/style-src, and per the CSP spec a nonce-source present in a
 * directive causes browsers to ignore 'unsafe-inline' in that same
 * directive entirely. Filament/Livewire's vendor-rendered inline
 * <style>/<script> tags don't carry our app's nonce, so combining Basic's
 * nonce with 'unsafe-inline' here would silently block them.
 */
final class AdminPanelPolicy implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::CONNECT, Keyword::SELF)
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::FONT, Keyword::SELF)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::FRAME, Keyword::SELF)
            ->add(Directive::MEDIA, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::SCRIPT, [Keyword::SELF, Keyword::UNSAFE_EVAL, Keyword::UNSAFE_INLINE, 'blob:'])
            ->add(Directive::STYLE, [Keyword::SELF, Keyword::UNSAFE_INLINE])
            ->add(Directive::IMG, [Keyword::SELF, 'data:', '*.tile.openstreetmap.org', 'https://ui-avatars.com']);
    }
}

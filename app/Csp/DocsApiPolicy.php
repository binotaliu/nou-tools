<?php

declare(strict_types=1);

namespace App\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

/**
 * CSP for the /docs/api Redoc page only, so the rest of the site doesn't need
 * to trust these hosts.
 *
 * Redoc renders with styled-components, which injects <style> tags (and style
 * attributes) it cannot give a nonce, so the nonce on `style-src` is dropped
 * and `'unsafe-inline'` allowed instead (a nonce makes browsers ignore it).
 * Scripts keep their nonce. It also uses a blob: worker for search and
 * data: images.
 */
final class DocsApiPolicy implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::CONNECT, Keyword::SELF)
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::FONT, [Keyword::SELF, 'data:'])
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::FRAME, Keyword::SELF)
            ->add(Directive::IMG, [Keyword::SELF, 'data:'])
            ->add(Directive::MEDIA, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::SCRIPT, [Keyword::SELF, 'cdn.redoc.ly'])
            ->addNonce(Directive::SCRIPT)
            ->add(Directive::STYLE, [Keyword::SELF, Keyword::UNSAFE_INLINE])
            ->add(Directive::WORKER, [Keyword::SELF, 'blob:']);
    }
}

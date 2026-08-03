<?php

declare(strict_types=1);

if (! function_exists('csp_nonce')) {
    /**
     * Return the current request's CSP nonce, for packages that render
     * inline `<script>` tags and check for this helper (e.g. genealabs/laravel-caffeine).
     */
    function csp_nonce(): string
    {
        return app('csp-nonce');
    }
}

<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Exceptions;

use RuntimeException;

/**
 * The editor's draft broke a rule the server enforces regardless of what
 * the model was told (size limits, slug, sources).
 */
final class InvalidWikiDraftException extends RuntimeException
{
    /**
     * @param  list<string>  $problems
     */
    public function __construct(public readonly array $problems)
    {
        parent::__construct('Invalid wiki draft: '.implode('; ', $problems));
    }
}

<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Exceptions;

use RuntimeException;

/**
 * A device, an IP or the whole site has used up its AI drafts for now. The
 * message is shown to the visitor.
 */
final class WikiDraftLimitReachedException extends RuntimeException {}

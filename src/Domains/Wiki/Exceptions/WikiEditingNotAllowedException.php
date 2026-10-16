<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Exceptions;

use RuntimeException;

/**
 * The visitor may not ask for this edit (no device, blocked, protected page).
 * The message is shown to the visitor.
 */
final class WikiEditingNotAllowedException extends RuntimeException {}

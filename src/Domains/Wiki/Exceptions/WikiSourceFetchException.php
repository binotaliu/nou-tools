<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Exceptions;

use RuntimeException;

/**
 * A source could not be fetched or read. The message says why, in the words
 * the editor tool hands back to the model.
 */
final class WikiSourceFetchException extends RuntimeException {}

<?php

declare(strict_types=1);

namespace NouTools\Domains\Wiki\Support;

final readonly class WikiPublishDecision
{
    /**
     * @param  list<string>  $reviewReasons  why the edit needs an admin, empty when it can publish
     */
    public function __construct(public array $reviewReasons) {}

    public function publishesAutomatically(): bool
    {
        return $this->reviewReasons === [];
    }
}

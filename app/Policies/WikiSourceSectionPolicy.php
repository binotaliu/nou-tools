<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WikiSourceSection;

final class WikiSourceSectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, WikiSourceSection $wikiSourceSection): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, WikiSourceSection $wikiSourceSection): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, WikiSourceSection $wikiSourceSection): bool
    {
        return $user->isAdmin();
    }
}

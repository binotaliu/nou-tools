<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WikiPage;

final class WikiPagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, WikiPage $wikiPage): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, WikiPage $wikiPage): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, WikiPage $wikiPage): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, WikiPage $wikiPage): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, WikiPage $wikiPage): bool
    {
        return $user->isAdmin();
    }
}

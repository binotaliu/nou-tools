<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WikiEditRequest;

final class WikiEditRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, WikiEditRequest $wikiEditRequest): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, WikiEditRequest $wikiEditRequest): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, WikiEditRequest $wikiEditRequest): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, WikiEditRequest $wikiEditRequest): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, WikiEditRequest $wikiEditRequest): bool
    {
        return $user->isAdmin();
    }
}

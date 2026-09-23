<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChangelogPost;
use App\Models\User;

final class ChangelogPostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, ChangelogPost $changelogPost): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ChangelogPost $changelogPost): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ChangelogPost $changelogPost): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, ChangelogPost $changelogPost): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, ChangelogPost $changelogPost): bool
    {
        return $user->isAdmin();
    }
}

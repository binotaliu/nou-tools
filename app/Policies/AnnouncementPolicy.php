<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

final class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Announcement $announcement): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Announcement $announcement): bool
    {
        return $user->isAdmin();
    }
}

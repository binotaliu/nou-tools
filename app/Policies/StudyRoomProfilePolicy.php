<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StudyRoomProfile;
use App\Models\User;

final class StudyRoomProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StudyRoomProfile $studyRoomProfile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StudyRoomProfile $studyRoomProfile): bool
    {
        return $user->isAdmin();
    }
}

<?php

namespace App\Policies;

use App\Models\DiscountStore;
use App\Models\User;

class DiscountStorePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isDiscountStoreManager();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DiscountStore $discountStore): bool
    {
        return $user->isAdmin() || $user->isDiscountStoreManager();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isDiscountStoreManager();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DiscountStore $discountStore): bool
    {
        return $user->isAdmin() || $user->isDiscountStoreManager();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DiscountStore $discountStore): bool
    {
        return $user->isAdmin() || $user->isDiscountStoreManager();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DiscountStore $discountStore): bool
    {
        return $user->isAdmin() || $user->isDiscountStoreManager();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DiscountStore $discountStore): bool
    {
        return $user->isAdmin() || $user->isDiscountStoreManager();
    }
}

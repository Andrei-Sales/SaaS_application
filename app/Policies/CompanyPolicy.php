<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * Determine whether the user can view the company.
     */
    public function view(User $user, Company $company): bool
    {
        // User can only view their own company
        return $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can update the company.
     */
    public function update(User $user, Company $company): bool
    {
        // Only owners can update company details
        return $user->isOwner() && $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can delete the company.
     */
    public function delete(User $user, Company $company): bool
    {
        // Only owners can delete their company
        return $user->isOwner() && $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can manage users.
     */
    public function manageUsers(User $user, Company $company): bool
    {
        // Only owners can manage company users
        return $user->isOwner() && $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can manage subscription.
     */
    public function manageSubscription(User $user, Company $company): bool
    {
        // Only owners can manage subscription
        return $user->isOwner() && $user->company_id === $company->id;
    }
}

<?php

namespace App\Policies;

use App\Models\Users\User;
use Illuminate\Auth\Access\Response;

class PartnerCollaboratorPolicy
{
    /**
     * Determine if the user can access admin panel resources.
     */
    public function accessAdminPanel(User $user): Response
    {
        return $user->isPartnerCollaborator()
            ? Response::deny('Partner collaborators cannot access the admin panel.')
            : Response::allow();
    }

    /**
     * Determine if the user can access company panel resources.
     */
    public function accessCompanyPanel(User $user): Response
    {
        return $user->isPartnerCollaborator()
            ? Response::deny('Partner collaborators cannot access the company panel.')
            : Response::allow();
    }

    /**
     * Determine if the user can access consultant panel resources.
     */
    public function accessConsultantPanel(User $user): Response
    {
        return $user->isPartnerCollaborator()
            ? Response::deny('Partner collaborators cannot access the consultant panel.')
            : Response::allow();
    }

    /**
     * Determine if the user can access guest panel resources.
     */
    public function accessGuestPanel(User $user): Response
    {
        return $user->isPartnerCollaborator()
            ? Response::deny('Partner collaborators cannot access the guest panel.')
            : Response::allow();
    }

    /**
     * Determine if the user can access user panel resources.
     */
    public function accessUserPanel(User $user): Response
    {
        return Response::allow(); // All authenticated users can access user panel
    }

    /**
     * Determine if the user can access data from other companies (tenant isolation).
     */
    public function accessTenantData(User $user, \Illuminate\Database\Eloquent\Model $tenant): Response
    {
        if ($user->isPartnerCollaborator()) {
            $partnerCompany = $user->getPartnerCompany();

            if (! $partnerCompany || ! $partnerCompany->is($tenant)) {
                return Response::deny('Partner collaborators can only access their own company data.');
            }
        }

        return Response::allow();
    }
}

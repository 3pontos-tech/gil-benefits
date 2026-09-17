<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Support;

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

/**
 * Quem pode ver a campanha de uma parceira: dono e gestor daquela empresa, e o admin.
 * Colaborador não — e beneficiário nunca está na empresa.
 */
final class PartnerVoucherAccess
{
    public static function allows(?User $user, Company $company): bool
    {
        if (! $user instanceof User) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isCompanyOwner($company)) {
            return true;
        }

        return $user->isCompanyManager($company);
    }
}

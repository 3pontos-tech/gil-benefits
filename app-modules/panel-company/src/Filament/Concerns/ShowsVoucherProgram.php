<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Concerns;

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Support\PartnerVoucherAccess;
use TresPontosTech\Vouchers\Models\VoucherBatch;

/**
 * As páginas da campanha só existem para empresa que tem lote, e só para quem manda nela.
 */
trait ShowsVoucherProgram
{
    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = auth()->user();

        /** @var Company|null $tenant */
        $tenant = filament()->getTenant();

        return $tenant instanceof Company
            && PartnerVoucherAccess::allows($user, $tenant)
            && VoucherBatch::query()->where('company_id', $tenant->getKey())->exists();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-company::resources.pages.vouchers.group');
    }

    protected static function partner(): Company
    {
        /** @var Company $tenant */
        $tenant = filament()->getTenant();

        return $tenant;
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Pages;

use App\Filament\Shared\Fields\TaxIdInput;
use App\Filament\Shared\Pages\EditUserProfile as BaseEditUserProfile;
use App\Models\Users\User;
use Filament\Schemas\Components\Component;

/**
 * O CPF fica no formulário, como no admin e no consultor: `user_details.tax_id` é obrigatório no
 * banco, e um dono de empresa cadastrado sem essa linha não conseguia salvar o próprio perfil.
 */
class EditUserProfile extends BaseEditUserProfile
{
    /**
     * @return array<Component>
     */
    protected function getExtraDetailFormComponents(): array
    {
        /** @var User $user */
        $user = $this->getUser();

        return [
            TaxIdInput::make()
                ->unique(table: 'user_details', column: 'tax_id', ignorable: $user->detail, ignoreRecord: false),
        ];
    }

    /**
     * @return array<string>
     */
    protected function getDetailFields(): array
    {
        return array_merge(parent::getDetailFields(), ['tax_id']);
    }
}

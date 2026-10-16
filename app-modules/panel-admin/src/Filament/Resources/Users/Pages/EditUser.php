<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Resources\Users\Pages;

use App\Models\Users\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use TresPontosTech\PanelAdmin\Filament\Actions\GrantExtraCreditAction;
use TresPontosTech\PanelAdmin\Filament\Resources\Users\UserResource;
use TresPontosTech\User\Actions\RevokeAppTokensAction;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Senha definida pelo admin desconecta os aparelhos do app da pessoa (RevokeAppTokensAction).
     */
    protected function afterSave(): void
    {
        $user = $this->getRecord();

        if ($user instanceof User && $user->wasChanged('password')) {
            resolve(RevokeAppTokensAction::class)->handle($user);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            GrantExtraCreditAction::forUser(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}

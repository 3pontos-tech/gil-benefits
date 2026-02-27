<?php

namespace TresPontosTech\Tenant\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Tenant\Actions\TenantSecretKeyRotationAction;

class TenantSecretKeyRotationPanelAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'tenant-secret-key-rotation';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->label('Gerar nova chave');
        $this->icon(Heroicon::ArrowPath);
        $this->visible(fn (): bool => auth()->user()->isAdmin() || auth()->user()->isCompanyOwner());
        $this->action(fn () => $this->rotateKey());
    }

    private function rotateKey(): void
    {
        /** @var Company $company */
        $company = filament()->getTenant();
        $key = resolve(TenantSecretKeyRotationAction::class)->generate($company);

        $this->getLivewire()->data['integration_access_key'] = $key;

        Notification::make('rotateKey')
            ->success()
            ->body('Nova chave gerada: ' . $key)
            ->send();
    }
}

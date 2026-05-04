<?php

declare(strict_types=1);

namespace App\Filament\Shared\Pages;

use App\Filament\FilamentPanel;
use Filament\Auth\Pages\Login;

class LoginPage extends Login
{
    public function mount(): void
    {
        parent::mount();

        if (! app()->environment('local')) {
            return;
        }

        $this->form->fill([
            ...$this->credentialsForCurrentPanel(),
            'remember' => true,
        ]);
    }

    /**
     * @return array{email: string, password: string}
     */
    private function credentialsForCurrentPanel(): array
    {
        $panelId = filament()->getCurrentPanel()?->getId();

        $email = match ($panelId) {
            FilamentPanel::Admin->value => 'admin@5pontos.com',
            FilamentPanel::Company->value => 'company@5pontos.com',
            FilamentPanel::Consultant->value => 'consultant@5pontos.com',
            FilamentPanel::User->value => 'employee@5pontos.com',
            default => '',
        };

        return [
            'email' => $email,
            'password' => 'password',
        ];
    }
}

<?php

declare(strict_types=1);
use TresPontosTech\PanelApp\Filament\Resources\SupportTickets\Pages\CreateSupportTicket;
use TresPontosTech\Support\Enums\SupportTicketCategoryEnum;

use function Pest\Livewire\livewire;

it('shows the profile link in the login hint inside the panel', function (): void {
    actingAsEmployee();
    livewire(CreateSupportTicket::class)
        ->set('data.category', SupportTicketCategoryEnum::LoginAccess->value)
        ->assertSee('Alterar minha senha no perfil');
});

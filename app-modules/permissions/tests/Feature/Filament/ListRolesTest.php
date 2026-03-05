<?php

use TresPontosTech\Permissions\Filament\Admin\Resources\Permissions\Pages\ListRoles;

use function Pest\Livewire\livewire;

it('should render', function (): void {
    actingAsSuperAdmin();
    livewire(ListRoles::class)
        ->assertOk();
});

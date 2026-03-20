<?php

use TresPontosTech\Admin\Filament\Resources\Permissions\Pages\CreateRole;

use function Pest\Livewire\livewire;

it('should render', function (): void {
    actingAsSuperAdmin();
    livewire(CreateRole::class)
        ->assertOk();
});

<?php

use TresPontosTech\Permissions\Filament\Admin\Resources\Permissions\Pages\EditRole;
use TresPontosTech\Permissions\Role;

use function Pest\Livewire\livewire;

it('should render', function (): void {
    actingAsSuperAdmin();
    livewire(EditRole::class, ['record' => Role::query()->first()->getKey()])
        ->assertOk();
});

<?php

use App\Models\Users\User;
use TresPontosTech\Admin\Filament\Resources\Users\Pages\ListUsers;

use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(ListUsers::class)
        ->assertOk();
});

it('should render all the users', function () {
    $users = User::factory()->count(8)->create();
    livewire(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords($users)
        ->assertCountTableRecords(9);
});

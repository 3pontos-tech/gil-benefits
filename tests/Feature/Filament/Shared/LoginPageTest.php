<?php

use App\Filament\Shared\Pages\LoginPage;

use function Pest\Livewire\livewire;

it('should render', function () {
    livewire(LoginPage::class)
        ->assertOk();
});

it('should fill login form on local and staging', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['app.env' => 'local']);
    filament()->setCurrentPanel(filament()->getPanel('app'));

    livewire(LoginPage::class)
        ->assertOk()
        ->assertSchemaStateSet([
            'email' => 'employee@5pontos.com',
            'password' => 'password',
            'remember' => true,
        ]);
});

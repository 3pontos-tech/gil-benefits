<?php

use App\Filament\Guest\Pages\LandingPage;

use function Pest\Livewire\livewire;

it('should render', function (): void {
    livewire(LandingPage::class)
        ->assertOk();
});

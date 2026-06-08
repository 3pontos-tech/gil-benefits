<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Company\Actions\AttachToDefaultCompany;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

// getDefaultTenant() must prefer the user's specific company over flamma-company
// so that employees are not landed on the internal Flamma tenant after login.

beforeEach(function (): void {
    filament()->setCurrentPanel(FilamentPanel::User->value);
    $this->panel = filament()->getCurrentPanel();
});

it('returns the specific company when the user belongs to one and also to flamma-company', function (): void {
    $user = User::factory()->employee()->create();

    resolve(AttachToDefaultCompany::class)->execute($user, Roles::Employee);

    $specificCompany = Company::factory()->create();
    $specificCompany->employees()->attach($user->getKey());

    expect($user->getDefaultTenant($this->panel)->getKey())
        ->toBe($specificCompany->getKey());
});

it('returns flamma-company when it is the only tenant the user belongs to', function (): void {
    $user = User::factory()->employee()->create();

    resolve(AttachToDefaultCompany::class)->execute($user, Roles::Employee);

    $flamma = Company::query()->where('slug', 'flamma-company')->first();

    expect($user->getDefaultTenant($this->panel)->getKey())
        ->toBe($flamma->getKey());
});

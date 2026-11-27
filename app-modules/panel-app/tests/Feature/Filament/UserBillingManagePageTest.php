<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\Filament\Pages\UserBillingManagePage;

use function Pest\Livewire\livewire;

it('shows the newest subscription that still pays, including one in default', function (): void {
    $user = User::factory()->employee()->create();
    $company = Company::factory()->create();
    $company->employees()->attach($user->getKey());
    Subscription::factory()->for($user, 'owner')->active()->create(['created_at' => now()->subYear(), 'ends_at' => now()->subMonth()]);
    $current = Subscription::factory()->for($user, 'owner')->defaulter()->create();

    filament()->setCurrentPanel(FilamentPanel::User->value);
    $this->actingAs($user);
    filament()->setTenant($company);

    livewire(UserBillingManagePage::class)
        ->assertOk()
        ->assertSet('subscription.id', $current->id);
});

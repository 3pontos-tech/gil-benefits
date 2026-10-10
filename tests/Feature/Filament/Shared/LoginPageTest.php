<?php

use App\Filament\Shared\Pages\LoginPage;
use App\Filament\Shared\Pages\RequestPasswordResetPage;
use App\Models\Users\User;
use Filament\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Livewire\livewire;

it('should render', function (): void {
    livewire(LoginPage::class)
        ->assertOk();
});

it('should fill login form on local and staging', function (): void {
    app()->detectEnvironment(fn (): string => 'local');
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

it('signs in whatever the capitalization typed', function (): void {
    filament()->setCurrentPanel(filament()->getPanel('admin'));
    $admin = User::factory()->admin()->create(['email' => 'gestor@empresa.com']);

    livewire(LoginPage::class)
        ->fillForm(['email' => 'Gestor@Empresa.COM', 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    assertAuthenticatedAs($admin);
});

it('sends the password reset link whatever the capitalization typed', function (): void {
    Notification::fake();
    filament()->setCurrentPanel(filament()->getPanel('admin'));
    $admin = User::factory()->admin()->create(['email' => 'gestor@empresa.com']);

    livewire(RequestPasswordResetPage::class)
        ->fillForm(['email' => 'Gestor@Empresa.com'])
        ->call('request')
        ->assertHasNoFormErrors();

    Notification::assertSentTo($admin, ResetPassword::class);
});

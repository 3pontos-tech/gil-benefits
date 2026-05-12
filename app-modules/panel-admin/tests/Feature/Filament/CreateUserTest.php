<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Admin\Filament\Resources\Users\Pages\CreateUser;
use TresPontosTech\User\Mail\WelcomeUserMail;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(fn () => actingAsAdmin());

it('creates a user and queues the welcome email with the typed password', function (): void {
    Mail::fake();

    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Novo Usuário',
            'email' => 'novo@user.com',
            'password' => 'senha-temporaria',
            'detail' => [
                'tax_id' => '976.923.250-57',
                'document_id' => '12.345.678-9',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, [
        'name' => 'Novo Usuário',
        'email' => 'novo@user.com',
    ]);

    Mail::assertQueued(
        WelcomeUserMail::class,
        fn (WelcomeUserMail $mail): bool => $mail->hasTo('novo@user.com')
            && $mail->password === 'senha-temporaria',
    );
});

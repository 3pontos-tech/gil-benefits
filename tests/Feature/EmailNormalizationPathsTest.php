<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Filament\Shared\Pages\EditUserProfile as SharedEditUserProfile;
use App\Filament\Shared\Pages\LoginPage;
use App\Filament\Shared\Pages\RegisterPage;
use App\Filament\Shared\Pages\RequestPasswordResetPage;
use App\Models\Users\Detail;
use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\PanelAdmin\Filament\Resources\Users\Pages\CreateUser;
use TresPontosTech\PanelCompany\Filament\Pages\Tenancy\EditTenantProfile;
use TresPontosTech\User\Actions\PersistImportedUsersAction;
use TresPontosTech\User\Actions\ValidateUserImportAction;
use TresPontosTech\User\Events\UserRegistered;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;
use function Pest\Livewire\livewire;

/**
 * Todos os caminhos que gravam ou buscam e-mail de usuário (#291). Cada teste parte de um
 * e-mail digitado com maiúsculas e confirma que ele vira a mesma conta que a forma minúscula.
 */
describe('a legacy account stored in uppercase before the change', function (): void {
    beforeEach(function (): void {
        $this->admin = User::factory()->admin()->create();
        DB::table('users')->where('id', $this->admin->id)->update(['email' => 'ADMIN@ADMIN.COM']);

        $migration = require database_path('migrations/2026_10_07_223123_normalize_user_emails.php');
        $migration->up();

        filament()->setCurrentPanel(FilamentPanel::Admin->value);
    });

    it('is stored in lowercase after the migration', function (): void {
        expect(DB::table('users')->where('id', $this->admin->id)->value('email'))->toBe('admin@admin.com');
    });

    it('signs in typing the original uppercase email', function (): void {
        livewire(LoginPage::class)
            ->fillForm(['email' => 'ADMIN@ADMIN.COM', 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        assertAuthenticatedAs($this->admin);
    });

    it('receives the password reset link typing the original uppercase email', function (): void {
        Notification::fake();

        livewire(RequestPasswordResetPage::class)
            ->fillForm(['email' => 'ADMIN@ADMIN.COM'])
            ->call('request')
            ->assertHasNoFormErrors();

        Notification::assertSentTo($this->admin, ResetPassword::class);
    });
});

it('lets the company panel registration refuse a differently cased duplicate', function (): void {
    User::factory()->create(['email' => 'dono@empresa.com']);
    filament()->setCurrentPanel(FilamentPanel::Company->value);

    livewire(RegisterPage::class)
        ->fillForm([
            'name' => 'Dono',
            'email' => 'Dono@Empresa.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ])
        ->call('register')
        ->assertHasFormErrors(['email' => 'unique']);

    assertDatabaseCount(User::class, 1);
});

it('lets the company panel registration store a new email in lowercase', function (): void {
    filament()->setCurrentPanel(FilamentPanel::Company->value);

    livewire(RegisterPage::class)
        ->fillForm([
            'name' => 'Dono',
            'email' => 'Dono@Empresa.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, ['email' => 'dono@empresa.com']);
});

it('lets the admin create a user in lowercase and refuse a differently cased duplicate', function (): void {
    Mail::fake();
    actingAsAdmin();
    User::factory()->create(['email' => 'existente@user.com']);

    livewire(CreateUser::class)
        ->fillForm(['name' => 'Dup', 'email' => 'Existente@User.com', 'password' => 'senha-temporaria'])
        ->call('create')
        ->assertHasFormErrors(['email' => 'unique']);

    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Novo',
            'email' => 'Novo@User.com',
            'password' => 'senha-temporaria',
            'detail' => ['tax_id' => '976.923.250-57', 'document_id' => '12.345.678-9'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, ['email' => 'novo@user.com']);
});

it('lets a company member invite store lowercase and refuse a differently cased duplicate', function (): void {
    Mail::fake();
    $owner = actingAsCompanyOwner();

    livewire(EditTenantProfile::class)
        ->callAction(TestAction::make('Invite Member')->table(), data: ['email' => strtoupper($owner->email)])
        ->assertHasFormErrors(['email' => 'unique']);

    livewire(EditTenantProfile::class)
        ->callAction(TestAction::make('Invite Member')->table(), data: [
            'name' => 'Funcionário',
            'email' => 'Funcionario@Empresa.com',
            'password' => 'password123',
            'detail' => ['tax_id' => '976.923.250-57', 'document_id' => '12.345.678-9', 'phone_number' => '+5511999999999'],
        ])
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, ['email' => 'funcionario@empresa.com']);
});

it("lets the profile keep its own email in another case and refuse someone else's", function (): void {
    $owner = actingAsCompanyOwner();
    User::factory()->create(['email' => 'outro@empresa.com']);

    livewire(SharedEditUserProfile::class)
        ->fillForm(['email' => 'Outro@Empresa.com', 'currentPassword' => 'password'])
        ->call('save')
        ->assertHasFormErrors(['email' => 'unique']);

    livewire(SharedEditUserProfile::class)
        ->fillForm(['email' => strtoupper($owner->email), 'currentPassword' => 'password'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($owner->fresh()->email)->toBe(strtolower($owner->email));
});

it('links a consultant registered in uppercase to the existing lowercase account', function (): void {
    Event::fake([UserRegistered::class]);
    $existing = User::factory()->create(['email' => 'joao@consultoria.com']);

    $consultant = Consultant::factory()->create(['email' => 'Joao@Consultoria.com']);

    expect($consultant->fresh()->user->getKey())->toBe($existing->getKey())
        ->and(User::query()->where('email', 'joao@consultoria.com')->count())->toBe(1);
});

it('creates the account of a new uppercase consultant in lowercase', function (): void {
    Event::fake([UserRegistered::class]);

    $consultant = Consultant::factory()->create(['email' => 'Maria@Consultoria.com']);

    expect($consultant->fresh()->user->email)->toBe('maria@consultoria.com');
});

it('lets the tenant API refuse a differently cased duplicate and store a new email in lowercase', function (): void {
    $company = Company::factory()->create();
    User::factory()->create(['email' => 'existente@acme.com']);
    $headers = [config('tenant.header') => $company->integration_access_key];

    postJson(route('api.v1.company.users.store', ['tenant' => $company->slug]), [
        'name' => 'Dup', 'email' => 'Existente@Acme.com', 'external_id' => '1',
    ], $headers)->assertUnprocessable()->assertJsonValidationErrors(['email']);

    postJson(route('api.v1.company.users.store', ['tenant' => $company->slug]), [
        'name' => 'Novo', 'email' => 'Novo@Acme.com', 'external_id' => '2',
    ], $headers)->assertCreated();

    assertDatabaseHas(User::class, ['email' => 'novo@acme.com']);
});

it('lets the app API sign in whatever the capitalization', function (): void {
    $user = defaultCompanyUser(['email' => 'Colaborador@Empresa.com']);

    expect($user->email)->toBe('colaborador@empresa.com');

    postJson(route('api.v1.auth.login'), [
        'email' => 'COLABORADOR@EMPRESA.COM', 'password' => 'password', 'device_name' => 'Android',
    ])->assertOk()->assertJsonPath('data.user.id', $user->id);
});

describe('spreadsheet import', function (): void {
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    function importRow(array $overrides = []): array
    {
        return [
            'name' => 'Importado',
            'email' => 'importado@empresa.com',
            'tax_id' => '97692325057',
            'phone_number' => '11999999999',
            'document_id' => '123456789',
            '__row_number' => 2,
            ...$overrides,
        ];
    }

    it('flags a row whose email exists in another case, accents included', function (): void {
        User::factory()->create(['email' => 'érica@empresa.com']);

        $errors = resolve(ValidateUserImportAction::class)
            ->execute(collect([importRow(['email' => 'ÉRICA@EMPRESA.COM'])]), Company::factory()->create());

        expect(collect($errors)->pluck('message'))->toContain('Email já cadastrado no sistema.');
    });

    it('flags a row whose email belongs to a deleted account', function (): void {
        User::factory()->create(['email' => 'saiu@empresa.com'])->delete();

        $errors = resolve(ValidateUserImportAction::class)
            ->execute(collect([importRow(['email' => 'saiu@empresa.com'])]), Company::factory()->create());

        expect(collect($errors)->pluck('message'))->toContain('Email já cadastrado no sistema.');
    });

    it('flags two rows that differ only in case as duplicates', function (): void {
        $errors = resolve(ValidateUserImportAction::class)->execute(collect([
            importRow(['email' => 'dup@empresa.com', '__row_number' => 2]),
            importRow(['email' => 'DUP@Empresa.com', 'tax_id' => '56259004770', '__row_number' => 3]),
        ]), Company::factory()->create());

        expect(collect($errors)->pluck('message'))->toContain('Email duplicado na planilha.');
    });

    it('stores imported emails in the same canonical form as the mutator', function (): void {
        Mail::fake();
        $company = Company::factory()->create();

        resolve(PersistImportedUsersAction::class)->execute(collect([
            importRow(['email' => '  ÉRICA@Empresa.COM ']),
        ]), $company);

        assertDatabaseHas(User::class, ['email' => 'érica@empresa.com']);
        expect(Detail::query()->count())->toBe(1);
    });
});

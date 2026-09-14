<?php

use App\Models\Users\Detail;
use App\Models\Users\User;
use Livewire\Livewire;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\Filament\Pages\UserRegistration;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;
use TresPontosTech\Vouchers\Support\VoucherRedemptionUrl;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

it('should render', function (): void {
    livewire(UserRegistration::class)
        ->assertOk();
});

it('should register user to flamma company', function (): void {
    livewire(UserRegistration::class)
        ->assertOk()
        ->fillForm([
            'name' => 'John',
            'email' => 'joe@doe.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
            'tax_id' => '562.590.047-70',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    assertDatabaseCount(User::class, 1);
    assertDatabaseHas(User::class, [
        'name' => 'John',
        'email' => 'joe@doe.com',
    ]);

    $user = User::query()->first();
    $flammaCompany = Company::query()->where('slug', 'flamma-company')->first();

    assertAuthenticatedAs($user);

    // The registrant is attached to Flamma with the employee role in the pivot.
    // (In this isolated test they are the first user, so they also become Flamma's
    // derived owner via companies.user_id — hence we assert the pivot role directly.)
    $membership = $user->companies()->where('slug', $flammaCompany->slug)->first();
    expect($membership)->not->toBeNull()
        ->and($membership->pivot->role)->toBe(Roles::Employee);
});

it('should not register a user with a tax_id that already exists', function (): void {
    Detail::factory()->create(['tax_id' => '56259004770']);

    livewire(UserRegistration::class)
        ->assertOk()
        ->fillForm([
            'name' => 'John',
            'email' => 'joe@doe.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
            'tax_id' => '562.590.047-70',
        ])
        ->call('register')
        ->assertHasFormErrors(['tax_id']);

    assertDatabaseMissing(User::class, ['email' => 'joe@doe.com']);
    assertDatabaseCount(Detail::class, 1);
});

it('should not register a user with a document_id that already exists', function (): void {
    Detail::factory()->create(['document_id' => '1234567890']);

    livewire(UserRegistration::class)
        ->assertOk()
        ->fillForm([
            'name' => 'John',
            'email' => 'joe@doe.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
            'tax_id' => '562.590.047-70',
            'document_id' => '123.456.789-0',
        ])
        ->call('register')
        ->assertHasFormErrors(['document_id']);

    assertDatabaseMissing(User::class, ['email' => 'joe@doe.com']);
    assertDatabaseCount(Detail::class, 1);
});

function registrationForm(array $overrides = []): array
{
    return [
        'name' => 'John',
        'email' => 'joe@doe.com',
        'password' => 'password123',
        'passwordConfirmation' => 'password123',
        'tax_id' => '562.590.047-70',
        ...$overrides,
    ];
}

function campaignCode(array $plan = []): VoucherCode
{
    return VoucherCode::factory()
        ->for(
            VoucherBatch::factory()->forPlan(CompanyPlan::factory()->active()->creditsOnly()->create($plan)),
            'batch',
        )
        ->create();
}

it('redeems the voucher informed at signup', function (): void {
    $code = campaignCode(['ends_at' => now()->addMonths(2)]);

    livewire(UserRegistration::class)
        ->fillForm(registrationForm(['voucher' => strtolower($code->code)]))
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'joe@doe.com')->sole();

    expect($user->hasActiveVoucherCredit())->toBeTrue()
        ->and($user->credits()->sole()->company_id)->toBe(Company::default()->getKey())
        ->and($code->fresh()->redemptions_count)->toBe(1);
});

it('registers just fine without a voucher', function (): void {
    livewire(UserRegistration::class)
        ->fillForm(registrationForm())
        ->call('register')
        ->assertHasNoFormErrors();

    expect(User::query()->where('email', 'joe@doe.com')->sole()->credits()->count())->toBe(0);
});

it('refuses an unknown voucher before creating anyone', function (): void {
    livewire(UserRegistration::class)
        ->fillForm(registrationForm(['voucher' => 'ZZZZ-ZZZZ']))
        ->call('register')
        ->assertHasFormErrors(['voucher']);

    assertDatabaseMissing(User::class, ['email' => 'joe@doe.com']);
});

it('refuses a voucher past the batch window', function (): void {
    $code = VoucherCode::factory()
        ->for(
            VoucherBatch::factory()
                ->forPlan(CompanyPlan::factory()->active()->creditsOnly()->create())
                ->state(['expires_at' => now()->subDay()]),
            'batch',
        )
        ->create();

    livewire(UserRegistration::class)
        ->fillForm(registrationForm(['voucher' => $code->code]))
        ->call('register')
        ->assertHasFormErrors(['voucher']);

    assertDatabaseMissing(User::class, ['email' => 'joe@doe.com']);
});

it('prefills the field from the card link', function (): void {
    $code = campaignCode();

    Livewire::withQueryParams([VoucherRedemptionUrl::QUERY_PARAMETER => strtolower($code->code)])
        ->test(UserRegistration::class)
        ->assertFormSet(['voucher' => $code->code]);
});

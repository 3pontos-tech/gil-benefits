<?php

use App\Filament\FilamentPanel;
use App\Http\Middleware\StoreSignupAttribution;
use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\Filament\Pages\UserRegistration;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;
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

    expect($user->holdsLiveVoucher())->toBeTrue()
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

it('stores the email in lowercase', function (): void {
    livewire(UserRegistration::class)
        ->fillForm(registrationForm(['email' => 'Joe.Doe@Example.COM']))
        ->call('register')
        ->assertHasNoFormErrors();

    assertDatabaseHas(User::class, ['email' => 'joe.doe@example.com']);
});

it('refuses an email that only differs in capitalization from an existing one', function (): void {
    User::factory()->create(['email' => 'joe@doe.com']);

    livewire(UserRegistration::class)
        ->fillForm(registrationForm(['email' => 'Joe@Doe.com']))
        ->call('register')
        ->assertHasFormErrors(['email' => 'unique']);

    assertDatabaseCount(User::class, 1);
});

it('tracks the signup on PostHog by the Flamma id', function (): void {
    config(['services.posthog.key' => 'phc_test']);
    Http::fake();

    livewire(UserRegistration::class)
        ->fillForm(registrationForm())
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::query()->sole();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request['event'] === 'cadastro_concluido'
        && $request['distinct_id'] === $user->getKey()
        && $request['properties'] === ['$set' => ['email' => 'joe@doe.com', 'name' => 'John'], 'source' => 'flamma', '$geoip_disable' => true]);
});

it('joins the Flare person to the Flamma user before tracking the signup with the first touch UTMs', function (): void {
    config(['services.posthog.key' => 'phc_test']);
    Http::fake();
    $flareId = (string) Str::uuid();
    Livewire::withCookies(signupAttribution([
        'flare_id' => $flareId,
        'utms' => ['utm_source' => 'flare', 'utm_medium' => 'whatsapp', 'utm_campaign' => 'gancho_pos_relatorio', 'utm_content' => 'variante_a'],
    ]));

    livewire(UserRegistration::class)
        ->fillForm(registrationForm())
        ->call('register')
        ->assertHasNoFormErrors();

    $user = User::query()->sole();
    $sent = Http::recorded()->map(fn (array $pair): array => [$pair[0]['event'], $pair[0]['distinct_id'], $pair[0]['properties']])->all();

    expect($sent)->toBe([
        ['$create_alias', $flareId, ['alias' => $user->getKey(), 'source' => 'flamma', '$geoip_disable' => true]],
        ['cadastro_concluido', $user->getKey(), [
            'utm_source' => 'flare',
            'utm_medium' => 'whatsapp',
            'utm_campaign' => 'gancho_pos_relatorio',
            'utm_content' => 'variante_a',
            '$set' => ['email' => 'joe@doe.com', 'name' => 'John'],
            'flare_id' => $flareId,
            'source' => 'flamma',
            '$geoip_disable' => true,
        ]],
    ])
        ->and(Cookie::queued(StoreSignupAttribution::COOKIE)?->getExpiresTime())->toBeLessThan(Date::now()->getTimestamp());
});

it('tracks the signup with the UTMs of an ad without a Flare person to join', function (): void {
    config(['services.posthog.key' => 'phc_test']);
    Http::fake();
    Livewire::withCookies(signupAttribution(['utms' => ['utm_source' => 'instagram', 'utm_campaign' => 'lancamento']]));

    livewire(UserRegistration::class)
        ->fillForm(registrationForm())
        ->call('register')
        ->assertHasNoFormErrors();

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request['event'] === 'cadastro_concluido'
        && $request['properties'] === ['utm_source' => 'instagram', 'utm_campaign' => 'lancamento', '$set' => ['email' => 'joe@doe.com', 'name' => 'John'], 'source' => 'flamma', '$geoip_disable' => true]);
});

it('keeps the attribution cookie when the signup fails inside the transaction', function (): void {
    config(['services.posthog.key' => 'phc_test']);
    Http::fake();
    Livewire::withCookies(signupAttribution(['flare_id' => (string) Str::uuid(), 'utms' => ['utm_source' => 'flare']]));
    filament()->setCurrentPanel(FilamentPanel::User->value);
    Event::listen(UserRegistered::class, function (): never {
        throw new RuntimeException('falha no cadastro');
    });

    expect(fn () => livewire(UserRegistration::class)->fillForm(registrationForm())->call('register'))
        ->toThrow(RuntimeException::class, 'falha no cadastro');

    Http::assertNothingSent();
    assertDatabaseCount(User::class, 0);
    expect(Cookie::hasQueued(StoreSignupAttribution::COOKIE))->toBeFalse();
});

it('sends nothing to PostHog on signup without a key', function (): void {
    Http::fake();
    Livewire::withCookies(signupAttribution(['flare_id' => (string) Str::uuid()]));

    livewire(UserRegistration::class)
        ->fillForm(registrationForm())
        ->call('register')
        ->assertHasNoFormErrors();

    Http::assertNothingSent();
});

/**
 * @param  array<string, mixed>  $attribution
 * @return array<string, string>
 */
function signupAttribution(array $attribution): array
{
    return [StoreSignupAttribution::COOKIE => (string) json_encode($attribution)];
}

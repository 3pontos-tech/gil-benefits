<?php

declare(strict_types=1);

use App\Http\Middleware\StoreSignupAttribution;
use App\Models\Users\User;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use TresPontosTech\PanelApp\Filament\Pages\EditUserProfile;
use TresPontosTech\Permissions\Roles;

beforeEach(function (): void {
    config(['services.posthog.key' => 'phc_test', 'services.posthog.host' => 'https://us.i.posthog.com']);
});

it('renders no script without a key', function (?string $key, string $route): void {
    config(['services.posthog.key' => $key]);

    $this->get(route($route))
        ->assertOk()
        ->assertDontSee('posthog', false);
})->with(['null' => null, 'empty' => ''])->with([
    'site' => 'site.home',
    'employee panel' => 'filament.app.auth.login',
    'company panel' => 'filament.company.auth.login',
]);

it('loads posthog-js tagged as Flamma on the site and the customer panels', function (string $route): void {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('posthog.init(' . Js::from('phc_test')->toHtml(), false)
        ->assertSee('api_host: ' . Js::from('https://us.i.posthog.com')->toHtml(), false)
        ->assertSee("person_profiles: 'identified_only'", false)
        ->assertSee("posthog.register({ source: 'flamma' })", false);
})->with([
    'site' => 'site.home',
    'collaborator page' => 'site.collaborator',
    'employee panel' => 'filament.app.auth.login',
    'employee signup' => 'filament.app.auth.register',
]);

it('keeps posthog-js out of the internal panels', function (string $route): void {
    $this->get(route($route))
        ->assertOk()
        ->assertDontSee('posthog', false);
})->with([
    'admin' => 'filament.admin.auth.login',
    'consultant' => 'filament.consultant.auth.login',
    'company' => 'filament.company.auth.login',
    'password reset request' => 'filament.app.auth.password-reset.request',
]);

it('identifies the signed in user by the Flamma id', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('site.home', ['flare_id' => (string) Str::uuid()]))
        ->assertOk()
        ->assertSee('posthog.identify(' . Js::from($user->getKey())->toHtml() . ')', false)
        ->assertDontSee('posthog.reset()', false);
});

it('identifies the signed in user inside the employee panel', function (): void {
    $user = actingAsEmployee();

    $this->get(EditUserProfile::getUrl(panel: 'app'))
        ->assertOk()
        ->assertSee('posthog.identify(' . Js::from($user->getKey())->toHtml() . ')', false);
});

it('identifies an anonymous visitor from the Flare invite by the Flare id', function (): void {
    $flareId = (string) Str::uuid();

    $this->get(route('site.home', ['flare_id' => $flareId]))
        ->assertOk()
        ->assertSee('posthog.identify(' . Js::from($flareId)->toHtml() . ')', false);
});

it('keeps identifying the visitor by the Flare id from the cookie on the signup page', function (): void {
    $flareId = (string) Str::uuid();

    $this->withCookie(StoreSignupAttribution::COOKIE, json_encode(['flare_id' => $flareId]))
        ->get(route('filament.app.auth.register'))
        ->assertOk()
        ->assertSee('posthog.identify(' . Js::from($flareId)->toHtml() . ')', false);
});

it('keeps the Flare id of the cookie when the URL brings an invalid one', function (): void {
    $flareId = (string) Str::uuid();

    $this->withCookie(StoreSignupAttribution::COOKIE, json_encode(['flare_id' => $flareId]))
        ->get(route('site.home', ['flare_id' => 'nao-e-uuid']))
        ->assertOk()
        ->assertSee('posthog.identify(' . Js::from($flareId)->toHtml() . ')', false);
});

it('registers the Flamma source after the reset, which clears the super properties', function (): void {
    $this->get(route('site.home'))
        ->assertOk()
        ->assertSeeInOrder(['posthog.reset();', "posthog.register({ source: 'flamma' })"], false);
});

it('keeps posthog-js away from the internal team on the site', function (): void {
    $this->actingAs(User::factory()->create()->assignRole(Roles::Consultant))
        ->get(route('site.home'))
        ->assertOk()
        ->assertDontSee('posthog', false);
});

it('turns off autocapture and session recording inside the employee panel only', function (): void {
    $this->get(route('filament.app.auth.login'))
        ->assertOk()
        ->assertSee('autocapture: false', false)
        ->assertSee('disable_session_recording: true', false);

    $this->get(route('site.home'))
        ->assertOk()
        ->assertDontSee('autocapture: false', false);
});

it('masks e-mail, tokens, signatures and voucher codes in the URLs before sending', function (): void {
    $this->get(route('filament.app.auth.register', ['voucher' => 'CODIGO123']))
        ->assertOk()
        ->assertSee('before_send: function (event)', false)
        ->assertSee('(?:email|token|signature|voucher)=', false);
});

it('resets a browser still identified as someone else before identifying the visitor', function (array $query, string $expectedId): void {
    $this->get(route('site.home', $query))
        ->assertOk()
        ->assertSee("if (posthog.get_property('\$user_state') === 'identified' && posthog.get_distinct_id() !== " . $expectedId . ')', false)
        ->assertSee('posthog.reset();', false);
})->with([
    'from the Flare invite' => [['flare_id' => '6f1c2a4e-1b2c-4d3e-8f9a-0b1c2d3e4f5a'], Js::from('6f1c2a4e-1b2c-4d3e-8f9a-0b1c2d3e4f5a')->toHtml()],
    'without a Flare id' => [[], 'null'],
]);

it('does not identify a visitor without a valid Flare id', function (array $query): void {
    $this->get(route('site.home', $query))
        ->assertOk()
        ->assertSee('posthog.init(', false)
        ->assertDontSee('posthog.identify(', false);
})->with([
    'no flare_id' => [[]],
    'invalid flare_id' => [['flare_id' => '<script>alert(1)</script>']],
]);

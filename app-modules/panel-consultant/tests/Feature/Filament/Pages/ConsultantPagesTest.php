<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Navigation\NavigationItem;
use TresPontosTech\PanelConsultant\Filament\Pages\ConsultantDashboard;
use TresPontosTech\PanelConsultant\Filament\Pages\ConsultantSchedule;
use TresPontosTech\PanelConsultant\Filament\Pages\EditConsultantProfile;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

it('renders the consultant dashboard page', function (): void {
    actingAsConsultant();

    livewire(ConsultantDashboard::class)
        ->assertOk();
});

it('allows the consultant to access the dashboard route', function (): void {
    actingAsConsultant();

    get(ConsultantDashboard::getUrl())
        ->assertSuccessful();
});

it('blocks a regular user from accessing the consultant dashboard', function (): void {
    $url = ConsultantDashboard::getUrl(panel: FilamentPanel::Consultant->value);

    actingAs(User::factory()->create());

    get($url)->assertForbidden();
});

it('keeps the consultant schedule page hidden via canAccess', function (): void {
    actingAsConsultant();

    expect(ConsultantSchedule::canAccess())->toBeFalse();
});

it('renders the consultant profile edit page', function (): void {
    actingAsConsultant();

    get(EditConsultantProfile::getUrl())
        ->assertSuccessful();
});

it('requires document_id when updating the consultant profile', function (): void {
    $consultant = actingAsConsultant();

    livewire(EditConsultantProfile::class)
        ->fillForm([
            'name' => $consultant->user->name,
            'email' => $consultant->user->email,
            'tax_id' => '123.456.789-09',
            'document_id' => '',
        ])
        ->call('save')
        ->assertHasFormErrors(['document_id' => 'required']);
});

function configureProductFormUrls(): void
{
    config(['panel-consultant.product_forms' => [
        'credit' => 'https://docs.example.com/credit-form',
        'investment' => 'https://docs.example.com/investment-form',
        'insurance' => 'https://docs.example.com/insurance-form',
    ]]);
}

function productFormLabel(string $form): string
{
    return __("panel-consultant::navigation.product_forms.{$form}");
}

function productsGroupLabel(): string
{
    return __('panel-consultant::navigation.groups.products');
}

function anchorTagWithHref(string $html, string $href): ?string
{
    $pattern = '/<a\b[^>]*\bhref="' . preg_quote($href, '/') . '"[^>]*>/';

    return preg_match($pattern, $html, $matches) === 1 ? $matches[0] : null;
}

it('shows the products group with the three forms in order', function (): void {
    configureProductFormUrls();
    actingAsConsultant();

    get(ConsultantDashboard::getUrl())
        ->assertSuccessful()
        ->assertSeeInOrder([
            productsGroupLabel(),
            productFormLabel('credit'),
            productFormLabel('investment'),
            productFormLabel('insurance'),
        ]);
});

it('links each product form to its configured url in a new tab', function (string $form): void {
    configureProductFormUrls();
    actingAsConsultant();

    $html = get(ConsultantDashboard::getUrl())->getContent();
    $formLink = anchorTagWithHref($html, "https://docs.example.com/{$form}-form");

    expect($formLink)->not->toBeNull()
        ->and($formLink)->toContain('target="_blank"');
})->with(['credit', 'investment', 'insurance']);

it('hides a product form when its url is not a valid web url', function (mixed $invalidUrl): void {
    configureProductFormUrls();
    config(['panel-consultant.product_forms.credit' => $invalidUrl]);
    actingAsConsultant();

    get(ConsultantDashboard::getUrl())
        ->assertSuccessful()
        ->assertDontSee(productFormLabel('credit'))
        ->assertSee(productFormLabel('investment'))
        ->assertSee(productFormLabel('insurance'));
})->with([
    'null' => [null],
    'empty string' => [''],
    'only spaces' => ['   '],
    'missing scheme' => ['docs.google.com/forms/abc'],
    'javascript scheme' => ['javascript:alert(1)'],
    'ftp scheme' => ['ftp://docs.example.com/form'],
    'boolean from env' => [true],
]);

it('trims spaces around a configured form url', function (): void {
    configureProductFormUrls();
    config(['panel-consultant.product_forms.credit' => '  https://docs.example.com/credit-form  ']);
    actingAsConsultant();

    get(ConsultantDashboard::getUrl())
        ->assertSuccessful()
        ->assertSee('href="https://docs.example.com/credit-form"', escape: false);
});

it('shows the products group with only the configured form', function (): void {
    config(['panel-consultant.product_forms' => [
        'credit' => null,
        'investment' => 'https://docs.example.com/investment-form',
        'insurance' => null,
    ]]);
    actingAsConsultant();

    get(ConsultantDashboard::getUrl())
        ->assertSuccessful()
        ->assertSeeInOrder([productsGroupLabel(), productFormLabel('investment')])
        ->assertDontSee(productFormLabel('credit'))
        ->assertDontSee(productFormLabel('insurance'));
});

it('hides the products group when no form url is configured', function (): void {
    config(['panel-consultant.product_forms' => [
        'credit' => null,
        'investment' => '',
        'insurance' => 'not-a-url',
    ]]);
    actingAsConsultant();

    get(ConsultantDashboard::getUrl())
        ->assertSuccessful()
        ->assertDontSee(productsGroupLabel())
        ->assertDontSee(productFormLabel('credit'))
        ->assertDontSee(productFormLabel('investment'))
        ->assertDontSee(productFormLabel('insurance'));
});

it('keeps the my profile item in the consultant navigation', function (): void {
    actingAsConsultant();

    get(ConsultantDashboard::getUrl())
        ->assertSee(__('all.my_profile'))
        ->assertSee(EditConsultantProfile::getUrl());
});

it('does not register the products group outside the consultant panel', function (FilamentPanel $panel): void {
    $productsGroup = productsGroupLabel();

    $groupsInPanel = collect(filament()->getPanel($panel->value)->getNavigationItems())
        ->map(fn (NavigationItem $item): ?string => $item->getGroup());

    expect($groupsInPanel)->not->toContain($productsGroup);
})->with([FilamentPanel::Company, FilamentPanel::User, FilamentPanel::Admin]);

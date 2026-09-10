<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\PanelApp\Filament\Pages\RedeemVoucherPage;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

function memberOfProgram(CompanyPlan $plan): User
{
    $user = User::factory()->create();
    $user->companies()->attach($plan->company_id, ['role' => Roles::Employee->value, 'active' => true]);

    return $user->fresh();
}

function actAsMemberOf(CompanyPlan $plan): User
{
    $user = memberOfProgram($plan);

    filament()->setCurrentPanel(FilamentPanel::User->value);
    actingAs($user);
    filament()->setTenant($plan->company);

    return $user;
}

function codeOf(CompanyPlan $plan): VoucherCode
{
    return VoucherCode::factory()
        ->for(VoucherBatch::factory()->forPlan($plan), 'batch')
        ->create();
}

it('is available to a member of a credits only program', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();
    actAsMemberOf($plan);

    expect(RedeemVoucherPage::canAccess())->toBeTrue();

    livewire(RedeemVoucherPage::class)->assertOk();
});

it('is hidden for a member of a monthly quota contract', function (): void {
    $plan = CompanyPlan::factory()->active()->create();
    actAsMemberOf($plan);

    expect(RedeemVoucherPage::canAccess())->toBeFalse();
});

it('is hidden for someone with no contract at all', function (): void {
    actingAs(User::factory()->create());

    expect(RedeemVoucherPage::canAccess())->toBeFalse();
});

it('redeems a valid code and grants the credit', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();
    $user = actAsMemberOf($plan);
    $code = codeOf($plan);

    livewire(RedeemVoucherPage::class)
        ->set('code', $code->code)
        ->call('redeem')
        ->assertHasNoErrors();

    expect($user->fresh()->hasAvailableCredit($plan->company_id))->toBeTrue()
        ->and($code->fresh()->redemptions_count)->toBe(1);
});

it('requires a code', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();
    actAsMemberOf($plan);

    livewire(RedeemVoucherPage::class)
        ->set('code')
        ->call('redeem')
        ->assertHasErrors(['code']);
});

it('grants nothing for an unknown code', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();
    actAsMemberOf($plan);

    livewire(RedeemVoucherPage::class)
        ->set('code', 'ZZZZ-ZZZZ')
        ->call('redeem');

    expect(UserCredit::query()->count())->toBe(0);
});

it('grants nothing for a code that belongs to another company', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();
    $otherProgram = CompanyPlan::factory()->active()->creditsOnly()->create();
    actAsMemberOf($plan);

    livewire(RedeemVoucherPage::class)
        ->set('code', codeOf($otherProgram)->code)
        ->call('redeem');

    expect(UserCredit::query()->count())->toBe(0);
});

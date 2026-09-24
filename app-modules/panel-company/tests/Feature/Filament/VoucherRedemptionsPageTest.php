<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\PanelCompany\Filament\Pages\VoucherRedemptionsPage;
use TresPontosTech\Vouchers\Actions\RedeemVoucher;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;
use TresPontosTech\Vouchers\Models\VoucherRedemption;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->companyOwner = actingAsCompanyOwner();
    $this->company = filament()->getTenant();

    Company::factory()->create(['slug' => Company::DEFAULT_SLUG]);

    $this->plan = CompanyPlan::factory()
        ->active()
        ->creditsOnly()
        ->for($this->company)
        ->create(['ends_at' => now()->addMonths(2)]);
    $this->batch = VoucherBatch::factory()->forPlan($this->plan)->create(['name' => 'Campanha Alfa']);
});

function redeemFromBatch(VoucherBatch $batch): VoucherRedemption
{
    $code = VoucherCode::factory()->for($batch, 'batch')->create();

    return resolve(RedeemVoucher::class)->handle(User::factory()->create(), $code->code);
}

it('lists who redeemed a voucher of this company', function (): void {
    $redemption = redeemFromBatch($this->batch);

    livewire(VoucherRedemptionsPage::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$redemption])
        ->assertSee($redemption->user->name)
        ->assertSee($redemption->code->code);
});

it('hides redemptions belonging to another partner', function (): void {
    $otherPlan = CompanyPlan::factory()->active()->creditsOnly()->create();
    $stranger = redeemFromBatch(VoucherBatch::factory()->forPlan($otherPlan)->create());

    livewire(VoucherRedemptionsPage::class)
        ->assertCanNotSeeTableRecords([$stranger]);
});

it('shows what became of the consultancy', function (): void {
    $redemption = redeemFromBatch($this->batch);
    $redemption->credits()->update(['status' => UserCreditStatusEnum::Used]);

    livewire(VoucherRedemptionsPage::class)
        ->assertSee(UserCreditStatusEnum::Used->getLabel());
});

it('stays hidden for a company with no campaign', function (): void {
    VoucherBatch::query()->delete();

    expect(VoucherRedemptionsPage::canAccess())->toBeFalse();
});

it('is closed to a plain employee', function (): void {
    $employee = User::factory()->employee()->create();
    $this->company->employees()->attach($employee->getKey());
    $this->actingAs($employee);

    expect(VoucherRedemptionsPage::canAccess())->toBeFalse();
});

<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\PanelCompany\Filament\Pages\VoucherCodesPage;
use TresPontosTech\PanelCompany\Filament\Widgets\VoucherCodesStatsWidget;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsCompanyOwner();
    $this->company = filament()->getTenant();

    $plan = CompanyPlan::factory()->active()->creditsOnly()->for($this->company)->create();
    $this->batch = VoucherBatch::factory()->forPlan($plan)->create();
    $this->redeemed = VoucherCode::factory()->count(2)->for($this->batch, 'batch')->create(['redemptions_count' => 1]);
    $this->free = VoucherCode::factory()->count(3)->for($this->batch, 'batch')->create(['redemptions_count' => 0]);
});

it('lists every code of the partner', function (): void {
    livewire(VoucherCodesPage::class)
        ->assertOk()
        ->assertCanSeeTableRecords($this->redeemed->merge($this->free));
});

it('tells free codes from redeemed ones', function (): void {
    livewire(VoucherCodesPage::class)
        ->filterTable('redeemed', true)
        ->assertCanSeeTableRecords($this->redeemed)
        ->assertCanNotSeeTableRecords($this->free)
        ->filterTable('redeemed', false)
        ->assertCanSeeTableRecords($this->free)
        ->assertCanNotSeeTableRecords($this->redeemed);
});

it('hides codes of other partners', function (): void {
    $stranger = VoucherCode::factory()
        ->for(VoucherBatch::factory()->forPlan(CompanyPlan::factory()->active()->creditsOnly()->create()), 'batch')
        ->create();

    livewire(VoucherCodesPage::class)->assertCanNotSeeTableRecords([$stranger]);
});

it('counts issued, still to use and redeemed', function (): void {
    livewire(VoucherCodesStatsWidget::class)
        ->assertOk()
        ->assertSee(__('panel-company::resources.pages.voucher_codes.stats.total'))
        ->assertSeeInOrder(['5', '3', '2']);
});

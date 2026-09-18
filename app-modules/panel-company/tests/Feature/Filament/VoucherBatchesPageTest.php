<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Pages\VoucherBatchesPage;
use TresPontosTech\PanelCompany\Support\PartnerVoucherUrls;
use TresPontosTech\Vouchers\Actions\BuildVoucherBatchPdf;
use TresPontosTech\Vouchers\Jobs\GenerateVoucherBatchPdfJob;
use TresPontosTech\Vouchers\Jobs\GenerateVoucherQrCodesJob;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->owner = actingAsCompanyOwner();
    $this->company = filament()->getTenant();
});

function partnerBatch(Company $company, int $codes = 5, int $redeemed = 2): VoucherBatch
{
    $plan = CompanyPlan::factory()->active()->creditsOnly()->for($company)->create();
    $batch = VoucherBatch::factory()->forPlan($plan)->create(['name' => 'Campanha Alfa', 'quantity' => $codes]);

    VoucherCode::factory()->count($redeemed)->for($batch, 'batch')->create(['redemptions_count' => 1]);
    VoucherCode::factory()->count($codes - $redeemed)->for($batch, 'batch')->create(['redemptions_count' => 0]);

    return $batch;
}

it('lists each campaign with what went out and what is left', function (): void {
    $batch = partnerBatch($this->company, codes: 5, redeemed: 2);

    livewire(VoucherBatchesPage::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$batch])
        ->assertTableColumnStateSet('codes_count', 5, $batch)
        ->assertTableColumnStateSet('redeemed_count', 2, $batch)
        ->assertTableColumnStateSet('available_count', 3, $batch);
});

it('hides campaigns of other partners', function (): void {
    partnerBatch($this->company);
    $stranger = VoucherBatch::factory()->forPlan(CompanyPlan::factory()->active()->creditsOnly()->create())->create();

    livewire(VoucherBatchesPage::class)
        ->assertCanNotSeeTableRecords([$stranger]);
});

it('queues the sheet when the campaign has no pdf yet', function (): void {
    Bus::fake();
    $batch = partnerBatch($this->company);

    livewire(VoucherBatchesPage::class)
        ->callAction(TestAction::make('downloadPdf')->table($batch));

    Bus::assertChained([GenerateVoucherQrCodesJob::class, GenerateVoucherBatchPdfJob::class]);
});

it('hands over the stored sheet instead of queueing again', function (): void {
    Bus::fake();
    Storage::fake('local');
    $batch = partnerBatch($this->company);

    resolve(GenerateVoucherBatchPdfJob::class, [
        'batchId' => $batch->getKey(),
        'requestedById' => $this->owner->getKey(),
        'downloadUrl' => PartnerVoucherUrls::pdf($batch),
    ])->handle(resolve(BuildVoucherBatchPdf::class));

    livewire(VoucherBatchesPage::class)
        ->callAction(TestAction::make('downloadPdf')->table($batch))
        ->assertRedirect(PartnerVoucherUrls::pdf($batch));

    Bus::assertNotDispatched(GenerateVoucherQrCodesJob::class);
    Bus::assertNotDispatched(GenerateVoucherBatchPdfJob::class);
});

it('stays hidden for a company with no campaign', function (): void {
    expect(VoucherBatchesPage::canAccess())->toBeFalse();
});

it('is closed to a plain employee', function (): void {
    partnerBatch($this->company);
    $employee = User::factory()->employee()->create();
    $this->company->employees()->attach($employee->getKey());
    $this->actingAs($employee);

    expect(VoucherBatchesPage::canAccess())->toBeFalse();
});

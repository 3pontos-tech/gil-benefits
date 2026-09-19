<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\PanelCompany\Support\PartnerVoucherUrls;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Vouchers\Actions\BuildVoucherBatchPdf;
use TresPontosTech\Vouchers\Jobs\GenerateVoucherBatchPdfJob;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    Storage::fake('local');

    $this->owner = actingAsCompanyOwner();
    $this->company = filament()->getTenant();

    $plan = CompanyPlan::factory()->active()->creditsOnly()->for($this->company)->create();
    $this->batch = VoucherBatch::factory()->forPlan($plan)->create();
    $this->code = VoucherCode::factory()->for($this->batch, 'batch')->create();
});

function sheetFor(VoucherBatch $batch, User $requester): void
{
    resolve(GenerateVoucherBatchPdfJob::class, [
        'batchId' => $batch->getKey(),
        'requestedById' => $requester->getKey(),
        'downloadUrl' => PartnerVoucherUrls::pdf($batch),
    ])->handle(resolve(BuildVoucherBatchPdf::class));
}

it('lets the owner download the sheet', function (): void {
    sheetFor($this->batch, $this->owner);

    get(PartnerVoucherUrls::pdf($this->batch))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('lets a manager of the company download the sheet', function (): void {
    sheetFor($this->batch, $this->owner);
    $manager = User::factory()->create();
    $this->company->employees()->attach($manager->getKey(), ['role' => Roles::CompanyManager->value]);

    actingAs($manager)->get(PartnerVoucherUrls::pdf($this->batch))->assertOk();
});

it('lets an admin download any sheet', function (): void {
    sheetFor($this->batch, $this->owner);

    actingAs(User::factory()->admin()->create())->get(PartnerVoucherUrls::pdf($this->batch))->assertOk();
});

it('refuses someone from another company', function (): void {
    sheetFor($this->batch, $this->owner);

    actingAs(User::factory()->create())->get(PartnerVoucherUrls::pdf($this->batch))->assertForbidden();
});

it('refuses a plain employee of the same company', function (): void {
    sheetFor($this->batch, $this->owner);
    $employee = User::factory()->create();
    $this->company->employees()->attach($employee->getKey(), ['role' => Roles::Employee->value]);

    actingAs($employee)->get(PartnerVoucherUrls::pdf($this->batch))->assertForbidden();
});

it('answers not found while the sheet has not been built', function (): void {
    get(PartnerVoucherUrls::pdf($this->batch))->assertNotFound();
});

it('serves the qr of a code to the owner', function (): void {
    $this->code->addMedia(UploadedFile::fake()->image('qr.png', 240, 240))
        ->toMediaCollection(VoucherCode::QR_CODE_COLLECTION, VoucherCode::QR_CODE_DISK);

    get(PartnerVoucherUrls::qr($this->code))
        ->assertOk()
        ->assertHeader('content-type', 'image/png');
});

it('keeps the qr away from strangers', function (): void {
    $this->code->addMedia(UploadedFile::fake()->image('qr.png', 240, 240))
        ->toMediaCollection(VoucherCode::QR_CODE_COLLECTION, VoucherCode::QR_CODE_DISK);

    actingAs(User::factory()->create())->get(PartnerVoucherUrls::qr($this->code))->assertForbidden();
});

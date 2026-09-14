<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Vouchers\Actions\BuildVoucherBatchPdf;
use TresPontosTech\Vouchers\Jobs\GenerateVoucherBatchPdfJob;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;
use TresPontosTech\Vouchers\Support\VoucherRedemptionUrl;

use function Pest\Laravel\travelTo;

function batchWithCodes(int $quantity = 7): VoucherBatch
{
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();
    $batch = VoucherBatch::factory()->forPlan($plan)->create(['quantity' => $quantity]);

    VoucherCode::factory()->count($quantity)->for($batch, 'batch')->create();

    return $batch;
}

it('renders one page for every six cards', function (): void {
    $pdf = resolve(BuildVoucherBatchPdf::class)->handle(batchWithCodes(7));

    expect($pdf->output())->toStartWith('%PDF-');
});

it('names the file after the batch', function (): void {
    $batch = batchWithCodes(1);
    $batch->update(['name' => 'Campanha de Lançamento']);

    expect(resolve(BuildVoucherBatchPdf::class)->fileName($batch))
        ->toBe('vouchers-campanha-de-lancamento.pdf');
});

it('points the redemption link at the signup form with the code', function (): void {
    $code = batchWithCodes(1)->codes()->sole();

    $url = VoucherRedemptionUrl::for($code);

    expect($url)->toContain(route(VoucherRedemptionUrl::ROUTE))
        ->and($url)->toContain('voucher=' . $code->code);
});

it('keeps a single qr image per code', function (): void {
    $code = batchWithCodes(1)->codes()->sole();

    expect($code->qrCode())->toBeNull()
        ->and($code->getMediaCollection(VoucherCode::QR_CODE_COLLECTION)?->singleFile)->toBeTrue();
});

it('files the qr image under the partner and the batch', function (): void {
    Storage::fake('public');
    travelTo(now());

    $batch = batchWithCodes(1);
    $batch->update(['name' => 'Campanha de Lançamento']);

    $code = $batch->codes()->sole();

    $media = $code
        ->addMedia(UploadedFile::fake()->image('qr.png', 240, 240))
        ->toMediaCollection(VoucherCode::QR_CODE_COLLECTION);

    expect($media->getPath())
        ->toContain(sprintf(
            'vouchers/%s/campanha-de-lancamento/%s/',
            Str::slug($batch->company->name),
            $media->id,
        ))
        ->and($media->getPath())->not->toContain($code->code);
});

it('stores the generated pdf on the private disk and notifies the requester', function (): void {
    Storage::fake('local');

    $batch = batchWithCodes(2);
    $admin = User::factory()->create();

    resolve(GenerateVoucherBatchPdfJob::class, [
        'batchId' => $batch->getKey(),
        'requestedById' => $admin->getKey(),
    ])->handle(resolve(BuildVoucherBatchPdf::class));

    $media = $batch->fresh()->pdf();

    expect($media)->not->toBeNull()
        ->and($media->disk)->toBe('local')
        ->and($media->mime_type)->toBe('application/pdf')
        ->and($media->getPath())->toContain('/pdf/')
        ->and($admin->notifications()->count())->toBe(1);
});

it('keeps only the latest pdf for a batch', function (): void {
    Storage::fake('local');

    $batch = batchWithCodes(1);
    $admin = User::factory()->create();

    foreach (range(1, 2) as $ignored) {
        resolve(GenerateVoucherBatchPdfJob::class, [
            'batchId' => $batch->getKey(),
            'requestedById' => $admin->getKey(),
        ])->handle(resolve(BuildVoucherBatchPdf::class));
    }

    expect($batch->fresh()->getMedia(VoucherBatch::PDF_COLLECTION))->toHaveCount(1);
});

it('does not queue a second job for the same batch', function (): void {
    Queue::fake();

    $batch = batchWithCodes(1);
    $admin = User::factory()->create();

    dispatch(new GenerateVoucherBatchPdfJob($batch->getKey(), $admin->getKey()));

    expect((new GenerateVoucherBatchPdfJob($batch->getKey(), $admin->getKey()))->uniqueId())
        ->toBe($batch->getKey());

    Queue::assertPushed(GenerateVoucherBatchPdfJob::class, 1);
});

it('makes every card link back to the redemption page', function (): void {
    $batch = batchWithCodes(2);
    $codes = $batch->codes;

    $output = resolve(BuildVoucherBatchPdf::class)->handle($batch)->output();

    expect($output)->toContain('/URI');

    $codes->each(function ($code) use ($output): void {
        expect($output)->toContain('voucher=' . $code->code);
    });
});

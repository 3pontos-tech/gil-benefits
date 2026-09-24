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
use TresPontosTech\Vouchers\Support\VoucherBatchPdfUrl;
use TresPontosTech\Vouchers\Support\VoucherRedemptionUrl;

use function Pest\Laravel\travelTo;

function batchWithCodes(int $quantity = 7): VoucherBatch
{
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();
    $batch = VoucherBatch::factory()->forPlan($plan)->create(['quantity' => $quantity]);

    VoucherCode::factory()->count($quantity)->for($batch, 'batch')->create();

    return $batch;
}

it('renders a front and a back for every card', function (): void {
    $output = resolve(BuildVoucherBatchPdf::class)->handle(batchWithCodes(3))->output();

    expect($output)->toStartWith('%PDF-')
        ->and(substr_count($output, '/Type /Page' . chr(10)))->toBe(6);
});

it('sizes the sheet to the configured width, keeping the 4:5 of the artwork', function (): void {
    config()->set('vouchers.card.width_mm', 120);

    $output = resolve(BuildVoucherBatchPdf::class)->handle(batchWithCodes(1))->output();

    expect($output)->toContain('/MediaBox [0.000 0.000 340.157 425.197]');
});

it('prints whichever deadline closes the redemption first', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create(['ends_at' => now()->addMonths(3)]);
    $batch = VoucherBatch::factory()->forPlan($plan)->create(['expires_at' => now()->addMonth()]);

    expect(resolve(BuildVoucherBatchPdf::class)->deadline($batch)?->toDateString())
        ->toBe(now()->addMonth()->toDateString());
});

it('says the card has no deadline when neither side carries one', function (): void {
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create(['ends_at' => null]);
    $batch = VoucherBatch::factory()->forPlan($plan)->create(['expires_at' => null]);

    expect(resolve(BuildVoucherBatchPdf::class)->deadline($batch))->toBeNull();
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
        'downloadUrl' => VoucherBatchPdfUrl::for($batch),
    ])->handle(resolve(BuildVoucherBatchPdf::class));

    $media = $batch->fresh()->pdf();

    expect($media)->not->toBeNull()
        ->and($media->disk)->toBe('local')
        ->and($media->mime_type)->toBe('application/pdf')
        ->and($media->getPath())->toContain('/pdf/')
        ->and($admin->notifications()->count())->toBe(1)
        ->and(data_get($admin->notifications()->first()->data, 'actions.0.url'))->toBe(VoucherBatchPdfUrl::for($batch));
});

it('sends the requester to whatever download url the panel handed in', function (): void {
    Storage::fake('local');

    $batch = batchWithCodes(1);
    $owner = User::factory()->create();

    resolve(GenerateVoucherBatchPdfJob::class, [
        'batchId' => $batch->getKey(),
        'requestedById' => $owner->getKey(),
        'downloadUrl' => 'https://partner.test/vouchers/sheet',
    ])->handle(resolve(BuildVoucherBatchPdf::class));

    expect(data_get($owner->notifications()->first()->data, 'actions.0.url'))
        ->toBe('https://partner.test/vouchers/sheet');
});

it('keeps only the latest pdf for a batch', function (): void {
    Storage::fake('local');

    $batch = batchWithCodes(1);
    $admin = User::factory()->create();

    foreach (range(1, 2) as $ignored) {
        resolve(GenerateVoucherBatchPdfJob::class, [
            'batchId' => $batch->getKey(),
            'requestedById' => $admin->getKey(),
            'downloadUrl' => VoucherBatchPdfUrl::for($batch),
        ])->handle(resolve(BuildVoucherBatchPdf::class));
    }

    expect($batch->fresh()->getMedia(VoucherBatch::PDF_COLLECTION))->toHaveCount(1);
});

it('does not queue a second job for the same batch', function (): void {
    Queue::fake();

    $batch = batchWithCodes(1);
    $admin = User::factory()->create();

    dispatch(new GenerateVoucherBatchPdfJob($batch->getKey(), $admin->getKey(), VoucherBatchPdfUrl::for($batch)));

    expect((new GenerateVoucherBatchPdfJob($batch->getKey(), $admin->getKey(), VoucherBatchPdfUrl::for($batch)))->uniqueId())
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

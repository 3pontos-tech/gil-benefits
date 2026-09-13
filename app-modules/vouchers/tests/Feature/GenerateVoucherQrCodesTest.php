<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Vouchers\Actions\GenerateVoucherQrCodes;
use TresPontosTech\Vouchers\Contracts\QrCodeGenerator;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;
use TresPontosTech\Vouchers\Support\ChillerlanQrCodeGenerator;

function batchOfCodes(int $quantity = 3): VoucherBatch
{
    $plan = CompanyPlan::factory()->active()->creditsOnly()->create();
    $batch = VoucherBatch::factory()->forPlan($plan)->create(['quantity' => $quantity]);

    VoucherCode::factory()->count($quantity)->for($batch, 'batch')->create();

    return $batch;
}

it('produces a png for the payload', function (): void {
    $image = resolve(QrCodeGenerator::class)->generate('https://example.test/redeem?voucher=ABCD-2345');

    expect(substr($image, 0, 8))->toBe("\x89PNG\r\n\x1a\n")
        ->and(resolve(QrCodeGenerator::class)->fileExtension())->toBe('png');
});

it('keeps producing valid images when called in a loop', function (): void {
    $generator = new ChillerlanQrCodeGenerator;

    foreach (range(1, 60) as $ignored) {
        $image = $generator->generate('https://example.test/app/partner/redeem-voucher?voucher=2EVG-WL4F');

        expect(substr($image, 0, 8))->toBe("\x89PNG\r\n\x1a\n");
    }
});

it('attaches one image per code on the private disk', function (): void {
    Storage::fake('local');

    $batch = batchOfCodes(4);

    $generated = resolve(GenerateVoucherQrCodes::class)->handle($batch);

    expect($generated)->toBe(4);

    $batch->codes->each(function (VoucherCode $code): void {
        $media = $code->qrCode();

        expect($media)->not->toBeNull()
            ->and($media->disk)->toBe('local')
            ->and($media->mime_type)->toBe('image/png')
            ->and($media->file_name)->toBe($code->code . '.png');
    });
});

it('encodes the redemption link of each code', function (): void {
    Storage::fake('local');

    $seen = new ArrayObject;

    app()->bind(QrCodeGenerator::class, fn (): QrCodeGenerator => new class($seen) implements QrCodeGenerator
    {
        public function __construct(private ArrayObject $seen) {}

        public function generate(string $data): string
        {
            $this->seen[] = $data;

            return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAAAAAA6fptVAAAACklEQVR4nGP6DwABBQECz6AuzQAAAABJRU5ErkJggg==');
        }

        public function fileExtension(): string
        {
            return 'png';
        }
    });

    $batch = batchOfCodes(2);
    resolve(GenerateVoucherQrCodes::class)->handle($batch);

    $payloads = $seen->getArrayCopy();

    expect($payloads)->toHaveCount(2);

    $batch->codes->each(function (VoucherCode $code) use ($payloads): void {
        expect($payloads)->toContain($code->redemptionUrl());
        expect($code->redemptionUrl())
            ->toContain($code->batch->company->slug)
            ->toContain('voucher=' . $code->code);
    });
});

it('replaces the image instead of stacking when run again', function (): void {
    Storage::fake('local');

    $batch = batchOfCodes(2);

    resolve(GenerateVoucherQrCodes::class)->handle($batch);
    resolve(GenerateVoucherQrCodes::class)->handle($batch);

    $batch->codes->each(function (VoucherCode $code): void {
        expect($code->getMedia(VoucherCode::QR_CODE_COLLECTION))->toHaveCount(1);
    });
});

<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Actions;

use TresPontosTech\Vouchers\Contracts\QrCodeGenerator;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;

final readonly class GenerateVoucherQrCodes
{
    public const CHUNK = 100;

    public function __construct(
        private QrCodeGenerator $qrCodeGenerator,
    ) {}

    public function handle(VoucherBatch $batch): int
    {
        $generated = 0;

        $batch->codes()
            ->with('batch.company')
            ->chunkById(self::CHUNK, function ($codes) use (&$generated): void {
                foreach ($codes as $code) {
                    $this->forCode($code);
                    ++$generated;
                }
            });

        return $generated;
    }

    public function forCode(VoucherCode $code): void
    {
        $image = $this->qrCodeGenerator->generate($code->redemptionUrl());

        $code->addMediaFromString($image)
            ->usingFileName(sprintf('%s.%s', $code->code, $this->qrCodeGenerator->fileExtension()))
            ->toMediaCollection(VoucherCode::QR_CODE_COLLECTION, VoucherCode::QR_CODE_DISK);
    }
}

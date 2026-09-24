<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use TresPontosTech\Vouchers\Actions\GenerateVoucherQrCodes;
use TresPontosTech\Vouchers\Models\VoucherBatch;

class GenerateVoucherQrCodesJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public bool $failOnTimeout = true;

    public function __construct(
        public string $batchId,
    ) {}

    public function uniqueId(): string
    {
        return $this->batchId;
    }

    public function tries(): int
    {
        return 3;
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30];
    }

    public function handle(GenerateVoucherQrCodes $generateQrCodes): void
    {
        $generateQrCodes->handle(VoucherBatch::with('company')->findOrFail($this->batchId));
    }

    public function failed(?Throwable $exception): void
    {
        logger()->error('Vouchers :: falha ao gerar os QR codes do lote', [
            'batch_id' => $this->batchId,
            'attempts' => $this->attempts(),
            'message' => $exception?->getMessage(),
        ]);
    }
}

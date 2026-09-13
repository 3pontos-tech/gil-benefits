<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Jobs;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use TresPontosTech\Vouchers\Actions\BuildVoucherBatchPdf;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Support\VoucherBatchPdfUrl;

class GenerateVoucherBatchPdfJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public function __construct(
        public string $batchId,
        public string $requestedById,
    ) {}

    public function uniqueId(): string
    {
        return $this->batchId;
    }

    public function tries(): int
    {
        return 2;
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [15];
    }

    public function handle(BuildVoucherBatchPdf $buildPdf): void
    {
        $batch = VoucherBatch::with('company')->findOrFail($this->batchId);

        $batch->addMediaFromString($buildPdf->handle($batch)->output())
            ->usingFileName($buildPdf->fileName($batch))
            ->toMediaCollection(VoucherBatch::PDF_COLLECTION, VoucherBatch::PDF_DISK);

        $requester = User::query()->find($this->requestedById);

        if (! $requester instanceof User) {
            return;
        }

        Notification::make()
            ->success()
            ->title(__('vouchers::vouchers.pdf.ready_title'))
            ->body(__('vouchers::vouchers.pdf.ready_body', ['batch' => $batch->name]))
            ->actions([
                Action::make('download')
                    ->label(__('vouchers::vouchers.pdf.download'))
                    ->url(VoucherBatchPdfUrl::for($batch))
                    ->openUrlInNewTab()
                    ->markAsRead(),
            ])
            ->sendToDatabase($requester);
    }

    public function failed(?Throwable $exception): void
    {
        logger()->error('Vouchers :: falha ao gerar o PDF do lote', [
            'batch_id' => $this->batchId,
            'attempts' => $this->attempts(),
            'message' => $exception?->getMessage(),
        ]);

        $requester = User::query()->find($this->requestedById);

        if (! $requester instanceof User) {
            return;
        }

        Notification::make()
            ->danger()
            ->title(__('vouchers::vouchers.pdf.failed_title'))
            ->body(__('vouchers::vouchers.pdf.failed_body'))
            ->sendToDatabase($requester);
    }
}

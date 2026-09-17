<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Actions;

use App\Models\Users\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Bus;
use TresPontosTech\Vouchers\Jobs\GenerateVoucherBatchPdfJob;
use TresPontosTech\Vouchers\Jobs\GenerateVoucherQrCodesJob;
use TresPontosTech\Vouchers\Models\VoucherBatch;

/**
 * Pede a folha de carteirinhas de um lote e avisa quem pediu.
 *
 * Os QR saem antes do PDF e cada um em seu job: são ~10s para 500 códigos e minutos para
 * desenhar as páginas, e nenhum dos dois cabe na requisição. A notificação de "pronto" sai
 * do próprio job do PDF, para quem pediu.
 */
final readonly class RequestVoucherBatchPdf
{
    public function handle(VoucherBatch $batch, User $requestedBy): void
    {
        Bus::chain([
            new GenerateVoucherQrCodesJob($batch->getKey()),
            new GenerateVoucherBatchPdfJob($batch->getKey(), $requestedBy->getKey()),
        ])->dispatch();

        Notification::make()
            ->info()
            ->title(__('vouchers::vouchers.pdf.queued_title'))
            ->body(__('vouchers::vouchers.pdf.queued_body'))
            ->send();
    }
}

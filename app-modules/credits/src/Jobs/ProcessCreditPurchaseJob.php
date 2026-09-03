<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TresPontosTech\Credits\Actions\SettleCreditOrder;
use TresPontosTech\Credits\Events\OrderCreditPurchased;

class ProcessCreditPurchaseJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 3600;

    public function __construct(public readonly OrderCreditPurchased $event) {}

    public function uniqueId(): string
    {
        return $this->event->creditOrderId;
    }

    public function handle(SettleCreditOrder $action): void
    {
        $action->handle($this->event->creditOrderId);
    }
}

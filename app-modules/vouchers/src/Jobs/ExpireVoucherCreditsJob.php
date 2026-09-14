<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use TresPontosTech\Vouchers\Actions\ExpireVoucherCredits;

class ExpireVoucherCreditsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function handle(ExpireVoucherCredits $expireVoucherCredits): void
    {
        $expireVoucherCredits->handle();
    }
}

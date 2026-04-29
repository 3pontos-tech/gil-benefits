<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use TresPontosTech\Billing\Barte\Jobs\HandleBarteWebhookJob;

class BarteWebhookController extends Controller
{
    public function handle(Request $request): void
    {
        $payload = $request->all();

        dispatch(new HandleBarteWebhookJob($payload));
    }
}

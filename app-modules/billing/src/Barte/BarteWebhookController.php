<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Billing\Barte\Actions\HandleBarteWebhook;
use TresPontosTech\Billing\Barte\DTOs\BarteWebhookDto;

class BarteWebhookController extends Controller
{
    public function __construct(private readonly HandleBarteWebhook $action) {}

    public function handle(Request $request): void
    {
        $payload = $request->all();

        Log::info('Barte webhook recebido', $payload);

        $this->action->handle(BarteWebhookDto::fromArray($payload));
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TresPontosTech\IntegrationMonday\Events\MondayItemColumnChanged;

final class MondayWebhookController
{
    public function handle(Request $request): JsonResponse
    {
        // On registration Monday POSTs a challenge that must be echoed back verbatim.
        if ($request->has('challenge')) {
            return response()->json(['challenge' => $request->input('challenge')]);
        }

        $event = $request->input('event', []);

        // Status columns carry the new label index under value.label.index.
        $index = $event['value']['label']['index'] ?? null;

        event(new MondayItemColumnChanged((string) ($event['boardId'] ?? ''), (string) ($event['pulseId'] ?? ''), (string) ($event['columnId'] ?? ''), $index === null ? null : (int) $index));

        return response()->json(['ok' => true]);
    }
}

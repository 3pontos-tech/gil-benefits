<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class ChatxStatusClient
{
    /**
     * Only an https URL counts: the bearer token and the customer's contact data go
     * in this request, and an http URL set by mistake would send them in clear text.
     */
    public function isConfigured(): bool
    {
        return str_starts_with((string) config('chatx.status_webhook.url'), 'https://')
            && filled(config('chatx.status_webhook.token'));
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ConnectionException
     */
    public function post(array $payload): Response
    {
        return Http::withToken((string) config('chatx.status_webhook.token'))
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->post((string) config('chatx.status_webhook.url'), $payload);
    }
}

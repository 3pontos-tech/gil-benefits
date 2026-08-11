<?php

declare(strict_types=1);

use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;

const CHATX_TEST_SECRET = 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2';

beforeEach(function (): void {
    config()->set('chatx.webhook_secret', CHATX_TEST_SECRET);
    config()->set('chatx.allowed_ips', []);
    config()->set('chatx.timestamp_tolerance', 600);
});

function chatxBody(): string
{
    return json_encode([
        'event' => 'ticket.created',
        'timestamp' => now()->toIso8601ZuluString(),
        'external_reference' => 'wpp-conv-a1b2c3d4',
        'visitor' => ['name' => 'João da Silva', 'email' => 'joao@empresa.com'],
        'ticket' => ['category' => 'financeiro', 'subject' => 'Boleto', 'description' => 'Não gerou.'],
    ], JSON_THROW_ON_ERROR);
}

function signChatx(string $timestamp, string $body, string $secret = CHATX_TEST_SECRET): string
{
    return hash_hmac('sha256', $timestamp . $body, $secret);
}

/**
 * Posts a raw body so the signature covers exactly the bytes sent — building the
 * request from an array would let the framework re-encode it and the digest would
 * stop matching for reasons unrelated to what each test is checking.
 *
 * @param  array<string, string>  $extraServer
 */
function postChatx(string $body, ?string $timestamp = null, ?string $signature = null, array $extraServer = []): TestResponse
{
    $timestamp ??= now()->toIso8601ZuluString();

    $headers = array_filter([
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_TIMESTAMP' => $timestamp,
        'HTTP_X_SIGNATURE' => $signature ?? signChatx($timestamp, $body),
    ]);

    return test()->call('POST', '/webhooks/chatx', [], [], [], array_merge($headers, $extraServer), $body);
}

it('lets through a request signed over the timestamp and the raw body', function (): void {
    $body = chatxBody();

    // 422 rather than 2xx: the signature gate opened, and the requester is unknown
    // in this test's empty database. Anything but 401 proves the middleware passed.
    postChatx($body)->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});

it('rejects a body altered after it was signed', function (): void {
    $timestamp = now()->toIso8601ZuluString();
    $signature = signChatx($timestamp, chatxBody());

    $tampered = str_replace('joao@empresa.com', 'atacante@empresa.com', chatxBody());

    postChatx($tampered, $timestamp, $signature)->assertUnauthorized();
});

it('rejects a signature made with the wrong secret', function (): void {
    $timestamp = now()->toIso8601ZuluString();
    $body = chatxBody();

    postChatx($body, $timestamp, signChatx($timestamp, $body, 'not-the-secret'))->assertUnauthorized();
});

it('rejects a request with no signature header', function (): void {
    postChatx(chatxBody(), signature: '')->assertUnauthorized();
});

it('rejects a request with no timestamp header', function (): void {
    $body = chatxBody();

    test()->call('POST', '/webhooks/chatx', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_SIGNATURE' => signChatx(now()->toIso8601ZuluString(), $body),
    ], $body)->assertUnauthorized();
});

it('refuses to authenticate when no secret is configured', function (): void {
    config()->set('chatx.webhook_secret');

    // Without this guard an empty secret would still produce a valid HMAC, and
    // anyone could sign their own requests.
    postChatx(chatxBody())->assertUnauthorized();
});

it('rejects a timestamp older than the tolerance', function (): void {
    $timestamp = now()->subMinutes(11)->toIso8601ZuluString();

    postChatx(chatxBody(), $timestamp)->assertUnauthorized();
});

it('rejects a timestamp too far in the future', function (): void {
    $timestamp = now()->addMinutes(11)->toIso8601ZuluString();

    postChatx(chatxBody(), $timestamp)->assertUnauthorized();
});

it('accepts a timestamp inside the ten minute window on either side', function (string $timestamp): void {
    postChatx(chatxBody(), $timestamp)->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
})->with([
    'nine minutes ago' => fn (): string => now()->subMinutes(9)->toIso8601ZuluString(),
    'nine minutes ahead' => fn (): string => now()->addMinutes(9)->toIso8601ZuluString(),
    'right now' => fn (): string => now()->toIso8601ZuluString(),
]);

it('rejects a timestamp with no explicit timezone', function (): void {
    // Read in the app timezone this would silently shift the window by hours.
    postChatx(chatxBody(), '2026-07-14T16:05:30')->assertUnauthorized();
});

it('rejects a timestamp that is not a date at all', function (): void {
    postChatx(chatxBody(), 'ontem')->assertUnauthorized();
});

it('accepts a timestamp with a numeric offset', function (): void {
    $timestamp = now()->setTimezone('America/Sao_Paulo')->toIso8601String();

    expect($timestamp)->toContain('-03:00');

    postChatx(chatxBody(), $timestamp)->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
});

describe('source ip allowlist', function (): void {
    it('rejects an address that is not listed', function (): void {
        config()->set('chatx.allowed_ips', ['203.0.113.10']);

        postChatx(chatxBody(), extraServer: ['REMOTE_ADDR' => '198.51.100.7'])->assertUnauthorized();
    });

    it('lets a listed address through', function (): void {
        config()->set('chatx.allowed_ips', ['203.0.113.10']);

        postChatx(chatxBody(), extraServer: ['REMOTE_ADDR' => '203.0.113.10'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('accepts an address inside a listed CIDR range', function (): void {
        config()->set('chatx.allowed_ips', ['203.0.113.0/24']);

        postChatx(chatxBody(), extraServer: ['REMOTE_ADDR' => '203.0.113.99'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    it('checks the address before the signature, so a blocked caller learns nothing', function (): void {
        config()->set('chatx.allowed_ips', ['203.0.113.10']);

        // Valid signature, wrong address: still a bare 401, same as every other
        // rejection. The response never says which gate closed.
        postChatx(chatxBody(), extraServer: ['REMOTE_ADDR' => '198.51.100.7'])->assertUnauthorized();
    });

    it('skips the check when the list is empty', function (): void {
        config()->set('chatx.allowed_ips', []);

        postChatx(chatxBody(), extraServer: ['REMOTE_ADDR' => '198.51.100.7'])
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    });
});

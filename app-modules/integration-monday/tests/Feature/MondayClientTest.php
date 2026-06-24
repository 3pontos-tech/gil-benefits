<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use TresPontosTech\IntegrationMonday\DTO\ChangeColumnValuesDTO;
use TresPontosTech\IntegrationMonday\DTO\CreateItemDTO;
use TresPontosTech\IntegrationMonday\DTO\UploadFileDTO;
use TresPontosTech\IntegrationMonday\Exceptions\MondayApiException;
use TresPontosTech\IntegrationMonday\MondayClient;

beforeEach(function (): void {
    config(['monday.token' => 'test-token', 'monday.api_url' => 'https://api.monday.com/v2']);
    Http::preventStrayRequests();
});

it('throws a non-retryable exception when the token is not configured', function (): void {
    config(['monday.token' => null]);

    expect(fn (): MondayClient => new MondayClient)
        ->toThrow(MondayApiException::class);
});

it('creates an item and returns its id', function (): void {
    Http::fake([
        'api.monday.com/*' => Http::response(['data' => ['create_item' => ['id' => '987654']]]),
    ]);

    $response = (new MondayClient)->createItem(new CreateItemDTO('111', 'topics', '[SUP-2026-0001] Subject', ['status' => ['index' => 0]]));

    expect($response->itemId)->toBe('987654');

    Http::assertSent(fn ($request): bool => $request['variables']['boardId'] === '111'
        && str_contains((string) $request['query'], 'create_item'));
});

it('sends a multiple column-values mutation', function (): void {
    Http::fake([
        'api.monday.com/*' => Http::response(['data' => ['change_multiple_column_values' => ['id' => '987654']]]),
    ]);

    (new MondayClient)->changeColumnValues(new ChangeColumnValuesDTO('987654', '111', [
        'status' => ['index' => 0],
        'date_col' => ['date' => '2026-06-22'],
    ]));

    Http::assertSent(fn ($request): bool => $request['variables']['columnValues'] === '{"status":{"index":0},"date_col":{"date":"2026-06-22"}}'
        && str_contains((string) $request['query'], 'change_multiple_column_values'));
});

it('throws a non-retryable exception when Monday returns GraphQL errors', function (): void {
    Http::fake([
        'api.monday.com/*' => Http::response(['errors' => [['message' => 'Invalid column']]]),
    ]);

    $exception = null;

    try {
        (new MondayClient)->createItem(new CreateItemDTO('111', 'topics', 'name', []));
    } catch (MondayApiException $mondayApiException) {
        $exception = $mondayApiException;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->retryable)->toBeFalse();
});

it('uploads a file to a column via the multipart endpoint', function (): void {
    Http::fake([
        'api.monday.com/v2/file' => Http::response(['data' => ['add_file_to_column' => ['id' => '1']]]),
    ]);

    (new MondayClient)->addFileToColumn(new UploadFileDTO('987654', 'file_col', 'binary-bytes', 'evidence.png'));

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.monday.com/v2/file'
        && $request->method() === 'POST');
});

it('throws when the file upload returns errors', function (): void {
    Http::fake([
        'api.monday.com/v2/file' => Http::response(['errors' => [['message' => 'too big']]]),
    ]);

    expect(fn () => (new MondayClient)->addFileToColumn(new UploadFileDTO('987654', 'file_col', 'bytes', 'f.png')))
        ->toThrow(MondayApiException::class);
});

it('throws a retryable exception on a transport failure', function (): void {
    Http::fake([
        'api.monday.com/*' => Http::response('boom', 500),
    ]);

    $exception = null;

    try {
        (new MondayClient)->createItem(new CreateItemDTO('111', 'topics', 'name', []));
    } catch (MondayApiException $mondayApiException) {
        $exception = $mondayApiException;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->retryable)->toBeTrue();
});

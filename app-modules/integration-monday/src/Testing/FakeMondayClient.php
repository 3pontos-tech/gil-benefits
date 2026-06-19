<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Testing;

use TresPontosTech\IntegrationMonday\Exceptions\MondayApiException;
use TresPontosTech\IntegrationMonday\MondayClient;
use TresPontosTech\IntegrationMonday\Responses\CreateItemResponse;

/**
 * In-memory MondayClient for tests: records every call and returns canned
 * responses without ever touching the network. Swap it in to guarantee a test
 * can never reach the real API:
 *
 *   $this->app->instance(MondayClient::class, $fake = new FakeMondayClient);
 */
final class FakeMondayClient extends MondayClient
{
    /** @var list<array<string, mixed>> */
    public array $createdItems = [];

    /** @var list<array<string, mixed>> */
    public array $statusChanges = [];

    /** @var list<array<string, mixed>> */
    public array $uploadedFiles = [];

    public bool $shouldFail = false;

    public bool $shouldFailUpload = false;

    private int $sequence = 0;

    public function __construct(
        public string $nextItemId = '',
    ) {
        // Intentionally skips the parent token validation — a fake needs no token.
    }

    /**
     * @param  array<string, mixed>  $columnValues
     */
    public function createItem(string $boardId, string $groupId, string $itemName, array $columnValues): CreateItemResponse
    {
        throw_if($this->shouldFail, MondayApiException::class, 'Fake Monday failure.', retryable: false);

        $itemId = $this->nextItemId !== '' ? $this->nextItemId : (string) (++$this->sequence);

        $this->createdItems[] = ['boardId' => $boardId, 'groupId' => $groupId, 'itemName' => $itemName, 'columnValues' => $columnValues] + ['itemId' => $itemId];

        return new CreateItemResponse($itemId);
    }

    public function changeStatus(string $itemId, string $boardId, string $columnId, int $index): void
    {
        throw_if($this->shouldFail, MondayApiException::class, 'Fake Monday failure.', retryable: false);

        $this->statusChanges[] = ['itemId' => $itemId, 'boardId' => $boardId, 'columnId' => $columnId, 'index' => $index];
    }

    public function addFileToColumn(string $itemId, string $columnId, string $contents, string $filename): void
    {
        throw_if($this->shouldFail || $this->shouldFailUpload, MondayApiException::class, 'Fake Monday upload failure.', retryable: false);

        $this->uploadedFiles[] = ['itemId' => $itemId, 'columnId' => $columnId, 'filename' => $filename] + ['size' => strlen($contents)];
    }
}

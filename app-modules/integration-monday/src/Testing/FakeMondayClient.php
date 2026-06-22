<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Testing;

use TresPontosTech\IntegrationMonday\DTO\ChangeColumnValuesDTO;
use TresPontosTech\IntegrationMonday\DTO\CreateItemDTO;
use TresPontosTech\IntegrationMonday\DTO\UploadFileDTO;
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
    public array $columnValueChanges = [];

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

    public function createItem(CreateItemDTO $data): CreateItemResponse
    {
        throw_if($this->shouldFail, MondayApiException::class, 'Fake Monday failure.', retryable: false);

        $itemId = $this->nextItemId !== '' ? $this->nextItemId : (string) (++$this->sequence);

        $this->createdItems[] = ['boardId' => $data->boardId, 'groupId' => $data->groupId, 'itemName' => $data->itemName, 'columnValues' => $data->columnValues, 'itemId' => $itemId];

        return new CreateItemResponse($itemId);
    }

    public function changeColumnValues(ChangeColumnValuesDTO $data): void
    {
        throw_if($this->shouldFail, MondayApiException::class, 'Fake Monday failure.', retryable: false);

        $this->columnValueChanges[] = ['itemId' => $data->itemId, 'boardId' => $data->boardId, 'columnValues' => $data->columnValues];
    }

    public function addFileToColumn(UploadFileDTO $data): void
    {
        throw_if($this->shouldFail || $this->shouldFailUpload, MondayApiException::class, 'Fake Monday upload failure.', retryable: false);

        $this->uploadedFiles[] = ['itemId' => $data->itemId, 'columnId' => $data->columnId, 'filename' => $data->filename, 'size' => strlen($data->contents)];
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use TresPontosTech\IntegrationMonday\DTO\ChangeStatusDTO;
use TresPontosTech\IntegrationMonday\DTO\CreateItemDTO;
use TresPontosTech\IntegrationMonday\DTO\UploadFileDTO;
use TresPontosTech\IntegrationMonday\Exceptions\MondayApiException;
use TresPontosTech\IntegrationMonday\Responses\CreateItemResponse;

class MondayClient
{
    private readonly string $apiUrl;

    private readonly PendingRequest $request;

    public function __construct()
    {
        throw_if(
            blank(config('monday.token')),
            MondayApiException::class,
            'Monday API token is not configured.',
            retryable: false,
        );

        $this->apiUrl = rtrim((string) config('monday.api_url'), '/');

        // Base request shared by every call: auth + JSON accept, but no body
        // format. Each method clones this and sets its own format (asJson for
        // GraphQL, asMultipart for file uploads) so the formats never leak.
        $this->request = Http::baseUrl($this->apiUrl)
            ->withHeaders(['Authorization' => (string) config('monday.token')])
            ->acceptJson();
    }

    public function createItem(CreateItemDTO $data): CreateItemResponse
    {
        $query = <<<'GRAPHQL'
            mutation ($boardId: ID!, $groupId: String!, $itemName: String!, $columnValues: JSON!) {
                create_item (board_id: $boardId, group_id: $groupId, item_name: $itemName, column_values: $columnValues) {
                    id
                }
            }
            GRAPHQL;

        $payload = $this->execute($query, [
            'boardId' => $data->boardId,
            'groupId' => $data->groupId,
            'itemName' => $data->itemName,
            'columnValues' => json_encode($data->columnValues),
        ]);

        return CreateItemResponse::make($payload);
    }

    /**
     * Sets a status column by its label index. Index is stable — it survives
     * label renames, casing and locale changes, unlike the label text (which
     * Monday matches exactly and rejects on any mismatch).
     */
    public function changeStatus(ChangeStatusDTO $data): void
    {
        $query = <<<'GRAPHQL'
            mutation ($itemId: ID!, $boardId: ID!, $columnId: String!, $value: JSON!) {
                change_column_value (item_id: $itemId, board_id: $boardId, column_id: $columnId, value: $value) {
                    id
                }
            }
            GRAPHQL;

        $this->execute($query, [
            'itemId' => $data->itemId,
            'boardId' => $data->boardId,
            'columnId' => $data->columnId,
            'value' => json_encode(['index' => $data->index]),
        ]);
    }

    /**
     * Uploads a file to a file column on an item. Uses Monday's multipart upload
     * endpoint (the GraphQL multipart spec), separate from the JSON endpoint.
     */
    public function addFileToColumn(UploadFileDTO $data): void
    {
        $mutation = sprintf(
            'mutation ($file: File!) { add_file_to_column (item_id: %s, column_id: "%s", file: $file) { id } }',
            $data->itemId,
            $data->columnId,
        );

        $response = (clone $this->request)
            ->attach('image', $data->contents, $data->filename)
            ->post($this->apiUrl . '/file', [
                'query' => $mutation,
                'map' => '{"image":"variables.file"}',
            ]);

        if ($response->failed() || filled($response->json('errors'))) {
            throw new MondayApiException(
                sprintf('Monday file upload failed: %s', $response->body()),
                $response->status(),
            );
        }
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    private function execute(string $query, array $variables): array
    {
        $response = (clone $this->request)->asJson()->post('', [
            'query' => $query,
            'variables' => $variables,
        ]);

        if ($response->failed()) {
            throw new MondayApiException(sprintf('Monday API request failed: %s', $response->body()), $response->status());
        }

        // Monday answers 200 with an "errors" array on GraphQL-level failures.
        if (filled($response->json('errors'))) {
            throw new MondayApiException(
                sprintf('Monday API returned errors: %s', json_encode($response->json('errors'))),
                retryable: false,
            );
        }

        return $response->json();
    }
}

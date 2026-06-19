<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use TresPontosTech\IntegrationMonday\Exceptions\MondayApiException;
use TresPontosTech\IntegrationMonday\Responses\CreateItemResponse;

class MondayClient
{
    public function __construct()
    {
        throw_if(
            blank(config('monday.token')),
            MondayApiException::class,
            'Monday API token is not configured.',
            retryable: false,
        );
    }

    /**
     * @param  array<string, mixed>  $columnValues
     */
    public function createItem(string $boardId, string $groupId, string $itemName, array $columnValues): CreateItemResponse
    {
        $query = <<<'GRAPHQL'
            mutation ($boardId: ID!, $groupId: String!, $itemName: String!, $columnValues: JSON!) {
                create_item (board_id: $boardId, group_id: $groupId, item_name: $itemName, column_values: $columnValues) {
                    id
                }
            }
            GRAPHQL;

        $payload = $this->execute($query, [
            'boardId' => $boardId,
            'groupId' => $groupId,
            'itemName' => $itemName,
            'columnValues' => json_encode($columnValues),
        ]);

        return CreateItemResponse::make($payload);
    }

    /**
     * Uploads a file to a file column on an item. Uses Monday's multipart upload
     * endpoint (the GraphQL multipart spec), separate from the JSON endpoint.
     */
    public function addFileToColumn(string $itemId, string $columnId, string $contents, string $filename): void
    {
        $mutation = sprintf(
            'mutation ($file: File!) { add_file_to_column (item_id: %s, column_id: "%s", file: $file) { id } }',
            $itemId,
            $columnId,
        );

        $response = Http::withHeaders(['Authorization' => (string) config('monday.token')])
            ->attach('image', $contents, $filename)
            ->post(rtrim((string) config('monday.api_url'), '/') . '/file', [
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
     * Sets a status column by its label index. Index is stable — it survives
     * label renames, casing and locale changes, unlike the label text (which
     * Monday matches exactly and rejects on any mismatch).
     */
    public function changeStatus(string $itemId, string $boardId, string $columnId, int $index): void
    {
        $query = <<<'GRAPHQL'
            mutation ($itemId: ID!, $boardId: ID!, $columnId: String!, $value: JSON!) {
                change_column_value (item_id: $itemId, board_id: $boardId, column_id: $columnId, value: $value) {
                    id
                }
            }
            GRAPHQL;

        $this->execute($query, [
            'itemId' => $itemId,
            'boardId' => $boardId,
            'columnId' => $columnId,
            'value' => json_encode(['index' => $index]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    private function execute(string $query, array $variables): array
    {
        $response = $this->request()->post('', [
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

    private function request(): PendingRequest
    {
        return Http::baseUrl((string) config('monday.api_url'))
            ->withHeaders(['Authorization' => (string) config('monday.token')])
            ->acceptJson()
            ->asJson();
    }
}

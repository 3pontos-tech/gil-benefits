<?php

namespace TresPontosTech\User\Actions;

use Illuminate\Support\Collection;
use Spatie\SimpleExcel\SimpleExcelReader;
use TresPontosTech\User\Exceptions\DuplicateImportHeadersException;

class ParseUsersFromFileAction
{
    /**
     * @return Collection<int, array<string, mixed>>
     *
     * @throws DuplicateImportHeadersException
     */
    public function execute(string $filePath, string $fileExtension): Collection
    {
        $reader = SimpleExcelReader::create($filePath, $fileExtension)
            ->formatHeadersUsing(fn (mixed $header): string => str((string) $header)->trim()->lower()->toString());

        $this->ensureUniqueHeaders($reader->getHeaders() ?? []);

        return $reader->getRows()
            ->collect()
            ->map(fn (array $row, int $index): array => [...$row, '__row_number' => $index + 2])
            ->reject(fn (array $row): bool => collect($row)->except(['__row_number'])->every(fn (mixed $value): bool => trim((string) $value) === ''))
            ->map(fn (array $row): array => $this->sanitizeRow($row))
            ->values();
    }

    /**
     * @param  array<mixed>  $headers
     */
    private function ensureUniqueHeaders(array $headers): void
    {
        $named = array_values(array_filter($headers, fn (mixed $header): bool => is_string($header) && $header !== ''));
        $duplicates = array_values(array_unique(array_diff_key($named, array_unique($named))));

        throw_if($duplicates !== [], DuplicateImportHeadersException::class, $duplicates);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function sanitizeRow(array $row): array
    {
        foreach (['phone_number', 'tax_id'] as $field) {
            if (isset($row[$field]) && $row[$field] !== '') {
                $row[$field] = preg_replace('/\D/', '', (string) $row[$field]);
            }
        }

        if (isset($row['document_id']) && $row['document_id'] !== '') {
            $row['document_id'] = preg_replace('/[^a-zA-Z0-9]/', '', (string) $row['document_id']);
        }

        return $row;
    }
}

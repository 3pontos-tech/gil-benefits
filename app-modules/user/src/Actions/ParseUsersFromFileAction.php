<?php

namespace TresPontosTech\User\Actions;

use Illuminate\Support\Collection;
use Spatie\SimpleExcel\SimpleExcelReader;

class ParseUsersFromFileAction
{
    public function execute(string $filePath, string $fileExtension): Collection
    {
        return SimpleExcelReader::create($filePath, $fileExtension)
            ->getRows()
            ->collect()
            ->map(fn (array $row, int $index): array => [...$row, '__row_number' => $index + 2])
            ->reject(fn (array $row): bool => collect($row)->except(['__row_number'])->every(fn (mixed $value): bool => trim((string) $value) === ''))
            ->map(fn (array $row): array => $this->sanitizeRow($row))
            ->values();
    }

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

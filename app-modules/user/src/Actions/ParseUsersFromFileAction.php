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
            ->reject(fn (array $row): bool => collect($row)->every(fn (mixed $value): bool => trim((string) $value) === ''))
            ->map(fn (array $row): array => $this->sanitizeRow($row))
            ->values();
    }

    private function sanitizeRow(array $row): array
    {
        foreach (['phone_number', 'tax_id', 'document_id'] as $field) {
            if (isset($row[$field]) && $row[$field] !== '') {
                $row[$field] = preg_replace('/\D/', '', (string) $row[$field]);
            }
        }

        return $row;
    }
}

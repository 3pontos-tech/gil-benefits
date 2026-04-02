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
            ->values();
    }
}

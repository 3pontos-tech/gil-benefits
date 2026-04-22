<?php

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

class CnpjColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(static function (mixed $state): ?string {
            if (! is_string($state) || $state === '') {
                return null;
            }

            $cnpj = preg_replace('/[^0-9]/', '', $state) ?? '';

            if (strlen($cnpj) !== 14) {
                return $state;
            }

            return preg_replace(
                '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/',
                '$1.$2.$3/$4-$5',
                $cnpj
            );
        });
    }
}

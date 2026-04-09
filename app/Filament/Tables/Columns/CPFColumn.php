<?php

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

class CPFColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(function (string $state): ?string {
            $cpf = preg_replace('/[^0-9]/', '', $state);

            if (strlen($cpf) === 11) {
                return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
            }

            return $state;
        });
    }
}

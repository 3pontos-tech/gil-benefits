<?php

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

class RGColumn extends TextColumn
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(static function (mixed $state): ?string {
            if (! is_string($state) || $state === '') {
                return null;
            }

            $rg = preg_replace('/[^0-9A-Za-z]/', '', $state) ?? '';

            if (strlen($rg) === 9) {
                return preg_replace('/(\d{2})(\d{3})(\d{3})([0-9Xx]{1})/', '$1.$2.$3-$4', $rg);
            }

            if (strlen($rg) === 8) {
                return preg_replace('/(\d{1})(\d{3})(\d{3})([0-9Xx]{1})/', '$1.$2.$3-$4', $rg);
            }

            return $state;
        });
    }
}

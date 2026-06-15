<?php

declare(strict_types=1);

use TresPontosTech\User\Actions\ParseUsersFromFileAction;

function makeParseTestCsv(array $rows, array $headers = ['name', 'email', 'document_id', 'tax_id', 'phone_number']): string
{
    $path = tempnam(sys_get_temp_dir(), 'parse_test_') . '.csv';
    $handle = fopen($path, 'w');
    fputcsv($handle, $headers);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return $path;
}

it('reads a CSV file and returns a Collection of arrays', function (): void {
    $csv = makeParseTestCsv([
        ['João Silva', 'joao@example.com', '12345678', '12345678901', '11999999999'],
        ['Maria Costa', 'maria@example.com', '87654321', '98765432100', '21988887777'],
    ]);

    $result = resolve(ParseUsersFromFileAction::class)->execute($csv, 'csv');

    expect($result)->toHaveCount(2)
        ->and($result[0]['email'])->toBe('joao@example.com')
        ->and($result[1]['email'])->toBe('maria@example.com');
});

it('skips completely empty rows', function (): void {
    $csv = makeParseTestCsv([
        ['João Silva', 'joao@example.com', '12345678', '12345678901', '11999999999'],
        ['', '', '', '', ''],
        ['Maria Costa', 'maria@example.com', '87654321', '98765432100', '21988887777'],
    ]);

    $result = resolve(ParseUsersFromFileAction::class)->execute($csv, 'csv');

    expect($result)->toHaveCount(2);
});

it('sanitizes tax_id and phone_number by removing non-numeric characters', function (): void {
    $csv = makeParseTestCsv([
        ['João Silva', 'joao@example.com', '12345678', '123.456.789-01', '(11) 99999-9999'],
    ]);

    $result = resolve(ParseUsersFromFileAction::class)->execute($csv, 'csv');

    expect($result[0]['tax_id'])->toBe('12345678901')
        ->and($result[0]['phone_number'])->toBe('11999999999');
});

it('adds __row_number to each row matching the spreadsheet line number', function (): void {
    $csv = makeParseTestCsv([
        ['João Silva', 'joao@example.com', '12345678', '12345678901', '11999999999'],
        ['Maria Costa', 'maria@example.com', '87654321', '98765432100', '21988887777'],
    ]);

    $result = resolve(ParseUsersFromFileAction::class)->execute($csv, 'csv');

    expect($result[0]['__row_number'])->toBe(2)
        ->and($result[1]['__row_number'])->toBe(3);
});

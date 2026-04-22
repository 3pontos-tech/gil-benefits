<?php

declare(strict_types=1);

use Spatie\SimpleExcel\SimpleExcelReader;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use function Pest\Laravel\get;

it('downloads the import template as a real xlsx file with header and example row', function (): void {
    $response = get(route('users.import-template.download'))
        ->assertOk()
        ->assertDownload('template-import-users.xlsx');

    /** @var BinaryFileResponse $binaryResponse */
    $binaryResponse = $response->baseResponse;
    $filePath = $binaryResponse->getFile()->getPathname();

    $rows = SimpleExcelReader::create($filePath)->getRows()->collect();

    expect($rows)->toHaveCount(1)
        ->and($rows->first())->toBe([
            'name' => 'João Silva',
            'email' => 'joaosilva@email.com',
            'phone_number' => '11987654321',
            'document_id' => '12345678',
            'tax_id' => '12345678901',
        ]);
});

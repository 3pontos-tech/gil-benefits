<?php

namespace TresPontosTech\User\Http\Controllers;

use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadImportTemplateController
{
    public function __invoke(): BinaryFileResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'template_') . '.xlsx';

        SimpleExcelWriter::create($tempPath)
            ->addHeader(['name', 'email', 'phone_number', 'document_id', 'tax_id'])
            ->addRow(['João Silva', 'joaosilva@email.com', '11987654321', '12345678', '12345678901'])
            ->close();

        return response()->download($tempPath, 'template-import-users.xlsx')->deleteFileAfterSend(true);
    }
}

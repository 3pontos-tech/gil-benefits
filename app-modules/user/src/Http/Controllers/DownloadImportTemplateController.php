<?php

namespace TresPontosTech\User\Http\Controllers;

use Illuminate\Support\Facades\File;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadImportTemplateController
{
    public function __invoke(): BinaryFileResponse
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'template_');
        $xlsxPath = $tempPath . '.xlsx';
        File::delete($tempPath);

        SimpleExcelWriter::create($xlsxPath)
            ->addHeader(['name', 'email', 'phone_number', 'document_id', 'tax_id', 'department'])
            ->addRow(['João Silva', 'joaosilva@email.com', '11987654321', '12345678', '12345678901', 'Financeiro'])
            ->close();

        return response()->download($xlsxPath, 'template-import-users.xlsx')->deleteFileAfterSend(true);
    }
}

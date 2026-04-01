<?php

namespace TresPontosTech\User\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadImportTemplateController
{
    public function __invoke(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            echo implode(',', ['name', 'email', 'phone_number', 'document_id', 'tax_id']) . "\n";
        }, 'template-import-users.csv');
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\User\Actions;

use Illuminate\Support\Facades\File;
use Spatie\SimpleExcel\SimpleExcelWriter;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\Support\DepartmentLookup;

class BuildImportTemplateAction
{
    public const string FILENAME = 'template-import-users.xlsx';

    public const string EMPLOYEES_SHEET = 'Funcionários';

    public const string DEPARTMENTS_SHEET = 'Departamentos';

    public function execute(Company $company): string
    {
        $departments = DepartmentLookup::for($company)->names();

        $tempPath = tempnam(sys_get_temp_dir(), 'template_');
        $xlsxPath = $tempPath . '.xlsx';
        File::delete($tempPath);

        $writer = SimpleExcelWriter::create($xlsxPath)
            ->nameCurrentSheet(self::EMPLOYEES_SHEET)
            ->addHeader(['name', 'email', 'phone_number', 'document_id', 'tax_id', 'department'])
            ->addRow(['João Silva', 'joaosilva@email.com', '11987654321', '12345678', '12345678901', $departments[0] ?? '']);

        $writer->addNewSheetAndMakeItCurrent(self::DEPARTMENTS_SHEET)
            ->addHeader(['department']);

        foreach ($departments as $department) {
            $writer->addRow([$department]);
        }

        $writer->close();

        return $xlsxPath;
    }
}

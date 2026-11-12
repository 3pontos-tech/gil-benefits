<?php

declare(strict_types=1);

use TresPontosTech\Company\Models\Company;
use TresPontosTech\Company\Models\Department;
use TresPontosTech\User\Support\DepartmentLookup;

it('adds the matching department id to each row and null when the cell is blank', function (): void {
    $company = Company::factory()->create();
    $engineering = Department::factory()->for($company)->create(['name' => 'Engenharia']);
    $finance = Department::factory()->for($company)->create(['name' => 'Financeiro']);
    Department::factory()->for($company)->create(['name' => '🚀']);

    $rows = collect([
        ['email' => 'a@example.com', 'department' => 'ENGENHARIA'],
        ['email' => 'b@example.com', 'department' => ' financeiro '],
        ['email' => 'c@example.com', 'department' => ''],
        ['email' => 'd@example.com', 'department' => "\u{200B}"],
        ['email' => 'e@example.com'],
    ]);

    $departmentIds = DepartmentLookup::for($company)->withDepartmentIds($rows)->pluck('department_id', 'email');

    expect($departmentIds->all())->toBe([
        'a@example.com' => $engineering->id,
        'b@example.com' => $finance->id,
        'c@example.com' => null,
        'd@example.com' => null,
        'e@example.com' => null,
    ]);
});

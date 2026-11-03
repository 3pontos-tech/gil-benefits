<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Models\UserCredit;

function onDeleteRuleOf(string $table, string $column): string
{
    $key = collect(Schema::getForeignKeys($table))
        ->first(fn (array $key): bool => $key['columns'] === [$column]);

    return strtolower((string) $key['on_delete']);
}

it('refuses to hard delete a company or a person with history', function (string $table, string $column): void {
    expect(onDeleteRuleOf($table, $column))->toBe('restrict');
})->with([
    ['companies', 'user_id'],
    ['appointments', 'company_id'],
    ['appointments', 'user_id'],
    ['appointments', 'consultant_id'],
    ['company_plans', 'company_id'],
    ['credit_grants', 'company_id'],
    ['credit_orders', 'company_id'],
    ['user_credits', 'company_id'],
    ['user_credits', 'owner_id'],
    ['user_credits', 'holder_id'],
    ['voucher_batches', 'company_id'],
]);

it('keeps a person profile when their company is hard deleted', function (): void {
    expect(onDeleteRuleOf('user_details', 'company_id'))->toBe('set null');
});

it('keeps departments following their company', function (): void {
    expect(onDeleteRuleOf('departments', 'company_id'))->toBe('cascade');
});

it('blocks the hard delete of a company that has appointments', function (): void {
    Schema::enableForeignKeyConstraints();
    $company = Company::factory()->create();
    Appointment::factory()->create(['company_id' => $company->getKey()]);

    expect(fn () => $company->forceDelete())->toThrow(QueryException::class);
});

it('blocks the hard delete of a person who holds credit', function (): void {
    Schema::enableForeignKeyConstraints();
    $credit = UserCredit::factory()->create();

    expect(fn () => $credit->holder->forceDelete())->toThrow(QueryException::class);
});

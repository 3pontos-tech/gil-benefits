<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Models\CreditGrant;
use TresPontosTech\Credits\Models\CreditOrder;
use TresPontosTech\Credits\Models\UserCredit;

$onDeleteRuleOf = function (string $table, string $column): string {
    $key = collect(Schema::getForeignKeys($table))
        ->first(fn (array $key): bool => $key['columns'] === [$column]);

    expect($key)->not->toBeNull(sprintf('No foreign key on %s.%s', $table, $column));

    return strtolower((string) $key['on_delete']);
};

it('refuses to hard delete rows that history points at', function (string $table, string $column) use ($onDeleteRuleOf): void {
    expect($onDeleteRuleOf($table, $column))->toBe('restrict');
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
    ['user_credits', 'appointment_id'],
    ['user_credits', 'voucher_redemption_id'],
    ['user_credits', 'grant_id'],
    ['user_credits', 'credit_order_id'],
    ['voucher_redemptions', 'voucher_code_id'],
    ['voucher_redemptions', 'user_id'],
    ['voucher_codes', 'voucher_batch_id'],
    ['voucher_batches', 'company_plan_id'],
    ['company_plans', 'plan_id'],
]);

it('keeps a person profile when their company is hard deleted', function () use ($onDeleteRuleOf): void {
    expect($onDeleteRuleOf('user_details', 'company_id'))->toBe('set null');
});

it('keeps departments following their company', function () use ($onDeleteRuleOf): void {
    expect($onDeleteRuleOf('departments', 'company_id'))->toBe('cascade');
});

it('blocks the hard delete of a company that has appointments', function (): void {
    $company = Company::factory()->create();
    Appointment::factory()->create(['company_id' => $company->getKey()]);

    expect(fn () => $company->forceDelete())->toThrow(QueryException::class);
});

it('blocks the hard delete of a catalogue plan that has contracts', function (): void {
    $contract = CompanyPlan::factory()->create();

    expect(fn () => $contract->plan->forceDelete())->toThrow(QueryException::class);
});

it('blocks the hard delete of a person who holds credit', function (): void {
    $credit = UserCredit::factory()->create();

    expect(fn () => $credit->holder->forceDelete())->toThrow(QueryException::class);
});

it('blocks the hard delete of a grant that produced credit', function (): void {
    $credit = UserCredit::factory()->create(['grant_id' => CreditGrant::factory()->create()->getKey()]);

    expect(fn () => $credit->grant->forceDelete())->toThrow(QueryException::class);
});

it('blocks the hard delete of an order that produced credit', function (): void {
    $credit = UserCredit::factory()->create(['credit_order_id' => CreditOrder::factory()->create()->getKey()]);

    expect(fn () => $credit->creditOrder->forceDelete())->toThrow(QueryException::class);
});

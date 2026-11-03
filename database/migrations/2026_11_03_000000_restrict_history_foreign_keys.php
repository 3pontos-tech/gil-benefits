<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rows that record money or care history stop following a hard delete of
 * their company or person: the database refuses instead of cascading.
 * A person's profile keeps living when the company goes, so it loses the
 * reference rather than the row. The same goes for the trail of what paid
 * for what: a used credit keeps its appointment and its voucher redemption,
 * redemptions keep their code, codes their batch, batches their contract,
 * and contracts their catalogue plan. Departments keep cascading, they have
 * no value of their own. See company/docs/adr/2026-11-03-no-hard-delete-with-history.md.
 */
return new class extends Migration
{
    /**
     * @var list<array{table: string, column: string, references: string, was: 'cascade'|'no action'|'set null', becomes: 'restrict'|'set null'}>
     */
    private const array KEYS = [
        ['table' => 'companies', 'column' => 'user_id', 'references' => 'users', 'was' => 'cascade', 'becomes' => 'restrict'],
        ['table' => 'appointments', 'column' => 'company_id', 'references' => 'companies', 'was' => 'cascade', 'becomes' => 'restrict'],
        ['table' => 'appointments', 'column' => 'user_id', 'references' => 'users', 'was' => 'no action', 'becomes' => 'restrict'],
        ['table' => 'appointments', 'column' => 'consultant_id', 'references' => 'consultants', 'was' => 'no action', 'becomes' => 'restrict'],
        ['table' => 'company_plans', 'column' => 'company_id', 'references' => 'companies', 'was' => 'cascade', 'becomes' => 'restrict'],
        ['table' => 'credit_grants', 'column' => 'company_id', 'references' => 'companies', 'was' => 'cascade', 'becomes' => 'restrict'],
        ['table' => 'credit_orders', 'column' => 'company_id', 'references' => 'companies', 'was' => 'cascade', 'becomes' => 'restrict'],
        ['table' => 'user_credits', 'column' => 'company_id', 'references' => 'companies', 'was' => 'cascade', 'becomes' => 'restrict'],
        ['table' => 'user_credits', 'column' => 'owner_id', 'references' => 'users', 'was' => 'cascade', 'becomes' => 'restrict'],
        ['table' => 'user_credits', 'column' => 'holder_id', 'references' => 'users', 'was' => 'cascade', 'becomes' => 'restrict'],
        ['table' => 'voucher_batches', 'column' => 'company_id', 'references' => 'companies', 'was' => 'cascade', 'becomes' => 'restrict'],
        ['table' => 'user_details', 'column' => 'company_id', 'references' => 'companies', 'was' => 'cascade', 'becomes' => 'set null'],
        ['table' => 'user_credits', 'column' => 'appointment_id', 'references' => 'appointments', 'was' => 'set null', 'becomes' => 'restrict'],
        ['table' => 'user_credits', 'column' => 'voucher_redemption_id', 'references' => 'voucher_redemptions', 'was' => 'set null', 'becomes' => 'restrict'],
        ['table' => 'voucher_redemptions', 'column' => 'voucher_code_id', 'references' => 'voucher_codes', 'was' => 'cascade', 'becomes' => 'restrict'],
        ['table' => 'voucher_redemptions', 'column' => 'user_id', 'references' => 'users', 'was' => 'cascade', 'becomes' => 'restrict'],
        ['table' => 'voucher_codes', 'column' => 'voucher_batch_id', 'references' => 'voucher_batches', 'was' => 'cascade', 'becomes' => 'restrict'],
        ['table' => 'voucher_batches', 'column' => 'company_plan_id', 'references' => 'company_plans', 'was' => 'cascade', 'becomes' => 'restrict'],
        ['table' => 'company_plans', 'column' => 'plan_id', 'references' => 'billing_plans', 'was' => 'cascade', 'becomes' => 'restrict'],
    ];

    public function up(): void
    {
        foreach (self::KEYS as $key) {
            $this->rebuild($key['table'], $key['column'], $key['references'], $key['becomes']);
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::KEYS) as $key) {
            $this->rebuild($key['table'], $key['column'], $key['references'], $key['was']);
        }
    }

    /**
     * @param  'cascade'|'no action'|'restrict'|'set null'  $onDelete
     */
    private function rebuild(string $table, string $column, string $references, string $onDelete): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            $blueprint->dropForeign([$column]);
        });

        Schema::table($table, function (Blueprint $blueprint) use ($column, $references, $onDelete): void {
            $blueprint->foreign($column)->references('id')->on($references)->onDelete($onDelete);
        });
    }
};

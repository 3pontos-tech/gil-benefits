<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Permissions\Roles;

return new class extends Migration
{
    /**
     * Backfill company_employees.role from the previous global-role model.
     *
     * The company role used to live only in Spatie's global roles table; the
     * pivot column existed but was NULL for every membership. This populates it
     * so per-tenant authorization has a source of truth.
     *
     * Must run BEFORE deploying the code that reads the pivot — otherwise every
     * membership would resolve to "no role".
     */
    public function up(): void
    {
        $rolesTable = config('permission.table_names.roles');
        $modelHasRoles = config('permission.table_names.model_has_roles');
        $morphKey = config('permission.column_names.model_morph_key');

        // 1. Real company owner (companies.user_id) -> owner.
        DB::table('company_employees')
            ->whereNull('role')
            ->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('companies')
                    ->whereColumn('companies.id', 'company_employees.company_id')
                    ->whereColumn('companies.user_id', 'company_employees.user_id');
            })
            ->update(['role' => Roles::CompanyOwner->value]);

        // 2. Users holding the global manager role -> manager.
        $managerUserIds = DB::table($modelHasRoles)
            ->join($rolesTable, $rolesTable . '.id', '=', $modelHasRoles . '.role_id')
            ->where($rolesTable . '.name', Roles::CompanyManager->value)
            ->pluck(sprintf('%s.%s', $modelHasRoles, $morphKey));

        if ($managerUserIds->isNotEmpty()) {
            DB::table('company_employees')
                ->whereNull('role')
                ->whereIn('user_id', $managerUserIds)
                ->update(['role' => Roles::CompanyManager->value]);
        }

        // 2b. Inside the shared "Flamma" company nobody is a manager -> employee.
        //     Resolves ambiguous managers who belong to Flamma plus their real company.
        DB::table('company_employees')
            ->where('role', Roles::CompanyManager->value)
            ->whereIn('company_id', function ($query): void {
                $query->select('id')->from('companies')->where('slug', 'flamma-company');
            })
            ->update(['role' => Roles::Employee->value]);

        // 3. Everyone else -> employee.
        DB::table('company_employees')
            ->whereNull('role')
            ->update(['role' => Roles::Employee->value]);
    }

    /**
     * One-time data backfill. There is no safe automatic reversal: nulling the
     * column back would also wipe roles written by the application after deploy.
     */
    public function down(): void
    {
        // no-op
    }
};

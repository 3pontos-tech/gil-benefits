<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TresPontosTech\Permissions\Roles;

return new class extends Migration
{
    private const string PAIR_INDEX = 'company_employees_company_id_user_id_unique';

    private const string ROLE_CHECK = 'company_employees_role_check';

    public function up(): void
    {
        $this->deleteDeadMemberships();

        Schema::table('company_employees', function (Blueprint $table): void {
            $table->uuid('id')->nullable()->first();
        });

        $this->fillIds();

        Schema::table('company_employees', function (Blueprint $table): void {
            $table->uuid('id')->nullable(false)->change();
            $table->primary('id');
            $table->string('role')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->index('user_id');
        });

        DB::statement(sprintf(
            'create unique index %s on company_employees (company_id, user_id) where deleted_at is null',
            self::PAIR_INDEX,
        ));

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('alter table company_employees alter column id set default gen_random_uuid()');
            DB::statement(sprintf(
                'alter table company_employees add constraint %s check (role in (%s))',
                self::ROLE_CHECK,
                implode(', ', array_map(fn (Roles $role): string => sprintf("'%s'", $role->value), $this->companyRoles())),
            ));
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(sprintf('alter table company_employees drop constraint if exists %s', self::ROLE_CHECK));
        }

        DB::statement(sprintf('drop index if exists %s', self::PAIR_INDEX));

        Schema::table('company_employees', function (Blueprint $table): void {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropPrimary();
            $table->dropColumn('id');
            $table->string('role')->nullable()->change();
        });
    }

    /**
     * Rows that point at a user or company that no longer exists (hard deleted, there was
     * no foreign key to stop it) or that was soft deleted. None of them is reachable by
     * the application, and the foreign keys below refuse the first group.
     */
    private function deleteDeadMemberships(): void
    {
        DB::table('company_employees')
            ->whereNotExists(fn (Builder $query): Builder => $query
                ->selectRaw('1')
                ->from('users')
                ->whereColumn('users.id', 'company_employees.user_id')
                ->whereNull('users.deleted_at'))
            ->orWhereNotExists(fn (Builder $query): Builder => $query
                ->selectRaw('1')
                ->from('companies')
                ->whereColumn('companies.id', 'company_employees.company_id')
                ->whereNull('companies.deleted_at'))
            ->delete();
    }

    private function fillIds(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('update company_employees set id = gen_random_uuid() where id is null');

            return;
        }

        $rows = DB::table('company_employees')
            ->whereNull('id')
            ->select(['company_id', 'user_id'])
            ->get();

        foreach ($rows as $row) {
            DB::table('company_employees')
                ->where('company_id', $row->company_id)
                ->where('user_id', $row->user_id)
                ->whereNull('id')
                ->update(['id' => (string) Str::uuid7()]);
        }
    }

    /**
     * @return list<Roles>
     */
    private function companyRoles(): array
    {
        return [Roles::CompanyOwner, Roles::CompanyManager, Roles::Employee];
    }
};

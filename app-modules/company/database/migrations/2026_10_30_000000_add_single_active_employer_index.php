<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Models\Company;

/**
 * One active employee membership per person, outside the default bucket.
 * The bucket id is a literal because a partial index cannot run a subquery;
 * a database without the bucket (fresh install, test suite) gets no index and
 * relies on the same rule enforced by TenantMember.
 */
return new class extends Migration
{
    private const string INDEX = 'company_employees_active_employer_unique';

    public function up(): void
    {
        $bucketId = DB::table('companies')->where('slug', Company::DEFAULT_SLUG)->value('id');

        if ($bucketId === null) {
            return;
        }

        DB::statement(sprintf(
            "create unique index %s on company_employees (user_id) where active = true and deleted_at is null and role = 'employee' and company_id <> '%s'",
            self::INDEX,
            $bucketId,
        ));
    }

    public function down(): void
    {
        DB::statement(sprintf('drop index if exists %s', self::INDEX));
    }
};

<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Actions;

use Illuminate\Support\Facades\DB;
use TresPontosTech\Company\Models\Company;

/**
 * Creates the partial unique index that keeps one active employee membership
 * per person outside the default bucket. The predicate carries the bucket id
 * as a literal, so the index can only exist once the bucket does; idempotent,
 * safe to run again on a database built from scratch after the seed.
 * See company/docs/adr/2026-10-30-single-active-employer.md.
 */
final class EnsureSingleEmployerIndex
{
    public const string INDEX = 'company_employees_active_employer_unique';

    public function handle(): bool
    {
        $bucketId = DB::table('companies')->where('slug', Company::DEFAULT_SLUG)->value('id');

        if ($bucketId === null) {
            return false;
        }

        DB::statement(sprintf(
            "create unique index if not exists %s on company_employees (user_id) where active = true and deleted_at is null and role = 'employee' and company_id <> '%s'",
            self::INDEX,
            $bucketId,
        ));

        return true;
    }

    public function drop(): void
    {
        DB::statement(sprintf('drop index if exists %s', self::INDEX));
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Laravel\Nightwatch\Facades\Nightwatch;
use Laravel\Nightwatch\Records\Query;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->rejectInfrastructureQueriesFromNightwatch();
    }

    public function register(): void
    {
        Relation::morphMap([
            'users' => User::class,
            'details' => Detail::class,
        ]);
    }

    /**
     * The queue and session tables are read and written by every job and every request,
     * and those queries never explain a failure, so sending them only spends Nightwatch
     * quota. Laravel always quotes table names in the SQL it builds, so looking for the
     * quoted name leaves failed_jobs and job_batches, which do help diagnose, untouched.
     */
    private function rejectInfrastructureQueriesFromNightwatch(): void
    {
        Nightwatch::rejectQueries(
            fn (Query $query): bool => str($query->sql)->contains(['"jobs"', '"sessions"']),
        );
    }
}

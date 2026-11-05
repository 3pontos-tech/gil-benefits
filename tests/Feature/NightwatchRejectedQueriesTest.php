<?php

declare(strict_types=1);

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Laravel\Nightwatch\Core;

/**
 * Nightwatch does not listen for queries while testing, so the query is handed to it
 * directly; what is left in the buffer is what would be sent.
 */
function nightwatchKeepsQuery(string $sql): bool
{
    $nightwatch = resolve(Core::class);
    $nightwatch->ingest->flush();

    $nightwatch->query(new QueryExecuted($sql, [], 1.0, DB::connection()));

    return $nightwatch->ingest->buffer->count() === 1;
}

it('drops the queries against the queue and session tables', function (string $sql): void {
    expect(nightwatchKeepsQuery($sql))->toBeFalse();
})->with([
    'job reserved by the worker' => 'select * from "jobs" where "queue" = ? and (("available_at" <= ? and "reserved_at" is null)) order by "id" asc limit 1 for update skip locked',
    'job pushed to the queue' => 'insert into "jobs" ("queue", "attempts", "reserved_at", "available_at", "created_at", "payload") values (?, ?, ?, ?, ?, ?)',
    'job marked as reserved' => 'update "jobs" set "reserved_at" = ?, "attempts" = ? where "id" = ?',
    'job deleted after running' => 'delete from "jobs" where "id" = ?',
    'session read' => 'select * from "sessions" where "id" = ? limit 1',
    'session created' => 'insert into "sessions" ("payload", "last_activity", "user_id", "ip_address", "user_agent", "id") values (?, ?, ?, ?, ?, ?)',
    'session refreshed' => 'update "sessions" set "payload" = ?, "last_activity" = ?, "user_id" = ?, "ip_address" = ?, "user_agent" = ? where "id" = ?',
    'expired sessions removed' => 'delete from "sessions" where "last_activity" <= ?',
]);

it('keeps every other query', function (string $sql): void {
    expect(nightwatchKeepsQuery($sql))->toBeTrue();
})->with([
    'failed job' => 'insert into "failed_jobs" ("uuid", "connection", "queue", "payload", "exception", "failed_at") values (?, ?, ?, ?, ?, ?)',
    'job batch' => 'select * from "job_batches" where "id" = ? limit 1',
    'cache' => 'select * from "cache" where "key" in (?)',
    'cache lock' => 'insert into "cache_locks" ("key", "owner", "expiration") values (?, ?, ?)',
    'appointment' => 'select * from "appointments" where "consultant_id" = ?',
    'schedule' => 'update "schedules" set "start_date" = ? where "id" = ?',
]);

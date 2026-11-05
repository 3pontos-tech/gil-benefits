<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The company integration API never had a client, so its credential goes
 * away with it (#316). Rolling back restores the NOT NULL column with a
 * fresh UUID per row, generated in PHP so it works on any driver.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('integration_access_key');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->uuid('integration_access_key')->nullable();
        });

        DB::table('companies')
            ->select('id')
            ->orderBy('id')
            ->each(function (object $company): void {
                DB::table('companies')
                    ->where('id', $company->id)
                    ->update(['integration_access_key' => (string) Str::uuid()]);
            });

        Schema::table('companies', function (Blueprint $table): void {
            $table->uuid('integration_access_key')->nullable(false)->change();
        });
    }
};

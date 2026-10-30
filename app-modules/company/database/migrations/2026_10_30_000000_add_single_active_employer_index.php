<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use TresPontosTech\Company\Actions\EnsureSingleEmployerIndex;

/**
 * One active employee membership per person, outside the default bucket.
 * A database without the bucket at this point (fresh install, test suite)
 * gets no index here; `company:single-employer-index` creates it later.
 */
return new class extends Migration
{
    public function up(): void
    {
        resolve(EnsureSingleEmployerIndex::class)->handle();
    }

    public function down(): void
    {
        resolve(EnsureSingleEmployerIndex::class)->drop();
    }
};

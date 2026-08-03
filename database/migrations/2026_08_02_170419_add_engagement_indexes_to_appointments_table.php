<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The engagement report filters appointments by date range — either across the
 * whole base or scoped to a set of companies. The existing
 * ['status', 'appointment_at'] index cannot serve those queries because the
 * range is on its second column, so both access paths get their own index.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->index('appointment_at');
            $table->index(['company_id', 'appointment_at']);
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex(['appointment_at']);
            $table->dropIndex(['company_id', 'appointment_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            // Add partner_code with all necessary attributes for Laravel 12 compliance
            $table->string('partner_code', 50)
                  ->unique()
                  ->nullable(true)
                  ->after('tax_id')
                  ->comment('Unique partner identification code for referral tracking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            // Drop unique constraint first, then column
            if (Schema::hasColumn('companies', 'partner_code')) {
                $table->dropUnique(['partner_code']);
                $table->dropColumn('partner_code');
            }
        });
    }
};

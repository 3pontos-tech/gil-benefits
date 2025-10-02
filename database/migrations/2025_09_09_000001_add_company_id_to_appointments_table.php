<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            if (! Schema::hasColumn('appointments', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->constrained('companies')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            if (Schema::hasColumn('appointments', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }
        });
    }
};

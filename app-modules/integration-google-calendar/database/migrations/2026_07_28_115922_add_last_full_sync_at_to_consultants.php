<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultants', function (Blueprint $table): void {
            $table->timestamp('last_full_sync_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('consultants', function (Blueprint $table): void {
            $table->dropColumn('last_full_sync_at');
        });
    }
};

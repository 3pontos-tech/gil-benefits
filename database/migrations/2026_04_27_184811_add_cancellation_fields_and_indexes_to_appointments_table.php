<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreignUuid('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_actor')->nullable();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'appointment_at']);
            $table->index('consultant_id');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_by', 'cancellation_actor']);

            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['status', 'appointment_at']);
            $table->dropIndex(['consultant_id']);
            $table->dropIndex(['company_id']);
        });
    }
};

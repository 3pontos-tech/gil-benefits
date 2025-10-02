<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consultant_id')->nullable()->constrained('consultants');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('voucher_id')->constrained('vouchers');
            $table->string('external_opportunity_id');
            $table->string('external_appointment_id');

            $table->string('category_type');
            $table->timestamp('appointment_at');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

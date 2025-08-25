<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_id')->constrained('consultants');
            $table->foreignId('voucher_id')->constrained('vouchers');
            $table->dateTime('date');
            $table->string('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

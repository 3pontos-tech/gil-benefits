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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->foreignUuid('company_id')->constrained('companies');
            $table->foreignUuid('consultant_id')->constrained('consultants');
            $table->foreignUuid('employee_id')->constrained('users');
            $table->enum('status', ['pending', 'active', 'used', 'expired'])->default('pending');
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();
        });
    }
};

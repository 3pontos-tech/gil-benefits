<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('consultant_id')->constrained('consultants');
            $table->foreignId('user_id')->nullable();
            $table->string('status')->comment("'pending', 'active', 'used', 'expired'");
            $table->dateTime('valid_until');
            $table->timestamps();
        });
    }
};

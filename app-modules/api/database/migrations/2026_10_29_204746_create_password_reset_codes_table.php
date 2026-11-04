<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_codes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code_hash', 64)->nullable();
            $table->unsignedSmallInteger('code_attempts')->default(0);
            $table->unsignedSmallInteger('failed_attempts')->default(0);
            $table->timestamp('code_sent_at')->nullable();
            $table->timestamp('code_expires_at')->nullable();
            $table->string('reset_token_hash', 64)->nullable();
            $table->timestamp('reset_token_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_codes');
    }
};

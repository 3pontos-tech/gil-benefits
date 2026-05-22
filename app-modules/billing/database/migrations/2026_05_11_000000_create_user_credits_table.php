<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_credits', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('holder_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('status')->default('available');
            $table->foreignUuid('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->timestamp('transferred_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('appointment_id');
            $table->index(['holder_id', 'status', 'created_at'], 'user_credits_consume_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_credits');
    }
};

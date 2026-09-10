<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_codes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('voucher_batch_id')->constrained('voucher_batches')->cascadeOnDelete();
            $table->string('code');
            $table->unsignedSmallInteger('max_redemptions')->default(1);
            $table->unsignedSmallInteger('redemptions_count')->default(0);
            $table->timestamps();

            $table->unique('code');
            $table->index('voucher_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_codes');
    }
};

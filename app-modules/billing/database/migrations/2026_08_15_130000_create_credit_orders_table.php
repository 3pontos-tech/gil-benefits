<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('provider');
            $table->string('checkout_id')->nullable();
            $table->string('billable_type');
            $table->string('billable_id');
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('amount_cents');
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['provider', 'checkout_id']);
            $table->index(['billable_type', 'billable_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_orders');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_redemptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('discount_id')->constrained('discounts')->restrictOnDelete();
            $table->foreignUuid('discount_grant_id')->nullable()->constrained('discount_grants')->restrictOnDelete();
            $table->uuidMorphs('billable');
            $table->string('plan_slug');
            $table->string('subscription_external_id')->unique();
            $table->string('status');
            $table->string('value_type');
            $table->unsignedTinyInteger('percent_off')->nullable();
            $table->unsignedInteger('amount_off_cents')->nullable();
            $table->string('duration');
            $table->unsignedSmallInteger('duration_cycles')->nullable();
            $table->unsignedInteger('list_amount_cents');
            $table->unsignedInteger('discounted_amount_cents');
            $table->unsignedSmallInteger('cycles_applied')->default(0);
            $table->timestampTz('reserved_until')->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();

            $table->index(['discount_id', 'status']);
            $table->index(['billable_type', 'billable_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_redemptions');
    }
};

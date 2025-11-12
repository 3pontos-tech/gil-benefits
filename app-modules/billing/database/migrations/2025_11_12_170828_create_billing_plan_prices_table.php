<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TresPontosTech\Billing\Core\Models\Plan;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_plan_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Plan::class, 'billing_plan_id')->constrained('billing_plans');
            $table->string('billing_scheme')->comment("'per_unit', 'tiered'");
            $table->string('tiers_mode')->comment("'volume', 'price'");
            $table->string('type')->comment("'recurring', 'one_time'");
            $table->integer('unit_amount_decimal');
            $table->boolean('active');
            $table->boolean('default')->default(false);
            $table->string('provider_price_id');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_plan_prices');
    }
};

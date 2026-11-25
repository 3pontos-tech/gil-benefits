<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('value_type')->default('percent');
            $table->unsignedTinyInteger('percent_off')->nullable();
            $table->unsignedInteger('amount_off_cents')->nullable();
            $table->string('duration')->default('forever');
            $table->unsignedSmallInteger('duration_cycles')->nullable();
            $table->string('distribution')->default('grant');
            $table->string('code')->nullable()->unique();
            $table->string('customer_type')->default('person');
            $table->string('audience')->nullable()->default('standalone');
            $table->json('eligible_plan_slugs')->nullable();
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->unsignedSmallInteger('claim_window_days')->nullable()->default(180);
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedSmallInteger('max_redemptions_per_customer')->nullable()->default(1);
            $table->boolean('first_subscription_only')->default(false);
            $table->boolean('locks_plan')->default(false);
            $table->boolean('active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->string('provider');
            $table->string('provider_product_id');
            $table->integer('trial_days')->nullable();
            $table->boolean('has_generic_trial');
            $table->boolean('allow_promotion_codes');
            $table->boolean('collect_tax_ids');
            $table->boolean('active')->default(false);
            $table->string('slug');
            $table->string('type');
            $table->string('unit_label')
                ->nullable()
                ->comment('the unit of what is being sold: a seat, an item or a month of subscription');
            $table->string('statement_descriptor')
                ->comment("the description that will be displayed on the customer's credit card statement");
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_plans');
    }
};

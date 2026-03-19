<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_plans', function (Blueprint $table): void {
            $table->string('provider_product_id')->nullable()->change();
            $table->string('statement_descriptor')->nullable()->change();
            $table->boolean('has_generic_trial')->nullable()->change();
            $table->boolean('allow_promotion_codes')->nullable()->change();
            $table->boolean('collect_tax_ids')->nullable()->change();
        });
    }
};

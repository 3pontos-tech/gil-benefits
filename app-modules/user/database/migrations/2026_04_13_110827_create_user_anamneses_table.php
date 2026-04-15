<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_anamneses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('life_moment');
            $table->text('main_motivation');
            $table->text('money_relationship');
            $table->text('plans_monthly_expenses');
            $table->text('tried_financial_strategies');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_anamneses');
    }
};

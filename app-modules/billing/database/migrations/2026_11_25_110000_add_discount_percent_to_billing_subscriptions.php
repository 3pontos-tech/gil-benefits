<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_subscriptions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('discount_percent')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('billing_subscriptions', function (Blueprint $table): void {
            $table->dropColumn('discount_percent');
        });
    }
};

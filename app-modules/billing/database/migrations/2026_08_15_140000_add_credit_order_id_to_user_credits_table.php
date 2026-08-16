<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_credits', function (Blueprint $table): void {
            $table->foreignUuid('credit_order_id')
                ->nullable()
                ->after('grant_id')
                ->constrained('credit_orders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_credits', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('credit_order_id');
        });
    }
};

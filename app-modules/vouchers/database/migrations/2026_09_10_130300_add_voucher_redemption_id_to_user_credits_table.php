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
            $table->foreignUuid('voucher_redemption_id')
                ->nullable()
                ->after('credit_order_id')
                ->constrained('voucher_redemptions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_credits', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('voucher_redemption_id');
        });
    }
};

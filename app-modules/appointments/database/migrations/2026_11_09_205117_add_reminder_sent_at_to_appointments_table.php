<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quando saiu o lembrete de 1 h antes do encontro (gil-benefits#349). Impede o segundo lembrete
 * e volta a vazio quando o horário muda, para o encontro remarcado ser lembrado de novo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->timestamp('reminder_sent_at')->nullable()->after('quota_refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('reminder_sent_at');
        });
    }
};

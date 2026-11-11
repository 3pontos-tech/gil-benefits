<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quando a pessoa foi avisada de que o crédito vence (gil-benefits#349): 7 dias antes e 1 dia
 * antes. Fica no banco, não no cache, porque é o que impede o aviso repetido e o que deixa uma
 * rodada perdida ser recuperada na seguinte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_credits', function (Blueprint $table): void {
            $table->timestamp('expiry_warned_week_before_at')->nullable();
            $table->timestamp('expiry_warned_day_before_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_credits', function (Blueprint $table): void {
            $table->dropColumn(['expiry_warned_week_before_at', 'expiry_warned_day_before_at']);
        });
    }
};

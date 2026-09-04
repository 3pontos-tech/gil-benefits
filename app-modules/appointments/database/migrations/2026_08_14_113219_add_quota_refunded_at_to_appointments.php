<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Carimbo da devolução de cota por cancelamento feito depois da virada do ciclo.
 *
 * Guarda a conclusão, não o fato bruto, porque a pergunta "esta consulta foi paga
 * com cota ou com crédito avulso?" só tem resposta no instante do cancelamento: o
 * listener que devolve o crédito zera `user_credits.appointment_id` logo depois, e
 * a cota nunca deixou registro em tabela nenhuma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->timestamp('quota_refunded_at')->nullable()->after('cancellation_actor');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('quota_refunded_at');
        });
    }
};

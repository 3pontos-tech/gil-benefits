<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dia em que o ciclo mensal de cota de uma assinatura individual vira.
 *
 * Coluna dedicada em vez de reusar `created_at`: aquele é carimbado no webhook de
 * PENDING, ou seja, antes do pagamento, e é metadado de infraestrutura que ninguém
 * pode corrigir para um cliente sem falsificar o histórico da linha. Nullable porque
 * uma assinatura pode existir em `pending` e nunca ser ativada.
 *
 * O preenchimento das linhas antigas fica na migration de backfill que vem em seguida,
 * separada por ser dado e não schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_subscriptions', function (Blueprint $table): void {
            $table->timestamp('quota_anchor_at')->nullable()->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('billing_subscriptions', function (Blueprint $table): void {
            $table->dropColumn('quota_anchor_at');
        });
    }
};

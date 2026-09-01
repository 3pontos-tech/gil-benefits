<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Valor mensal negociado de um contrato B2B (FLM-41, STORY-234).
 *
 * O contrato é a única forma de pagamento que a plataforma não sabe precificar:
 * assinatura tem faixa de assento ou preço cadastrado, contrato é negociado fora
 * da tabela e nunca foi registrado em lugar nenhum. Sem esta coluna, essas
 * empresas aparecem como "valor não cadastrado" no cockpit inteiro — ficam fora
 * do MRR, do ranking, do ticket médio e do risco de churn.
 *
 * Fica no contrato, e não na empresa, porque é ele que tem vigência: renegociar
 * cria uma linha nova, e o valor antigo continua valendo para os meses em que
 * esteve em vigor. É o que permite ao gráfico de evolução reconstruir o passado
 * sem inventar.
 *
 * Nullable de propósito: contrato sem valor preenchido continua sendo um estado
 * legítimo, e a tela diz isso em vez de exibir zero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_plans', function (Blueprint $table): void {
            $table->unsignedInteger('monthly_value_cents')->nullable()->after('seats');
        });
    }

    public function down(): void
    {
        Schema::table('company_plans', function (Blueprint $table): void {
            $table->dropColumn('monthly_value_cents');
        });
    }
};

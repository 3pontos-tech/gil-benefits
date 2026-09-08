<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * Preenche a data de início dos planos contratuais que ficaram sem ela.
 *
 * `starts_at` passou a ser a âncora do ciclo mensal de cota — o dia em que a consulta
 * de cada funcionário da empresa volta — e o campo era opcional no formulário do admin
 * até agora. Sem o backfill, esses contratos ancorariam no `created_at` do registro,
 * que é quando alguém digitou o plano no painel e não tem relação com o contrato.
 *
 * Sem alteração de schema: a coluna segue nullable no banco, e o fallback do resolver
 * continua como rede para dado legado. Feito em PHP e em lotes para não depender de
 * função de data com dialeto diferente entre pgsql e sqlite.
 *
 * Irreversível por desenho: depois do update não há como distinguir quais linhas eram
 * nulas, então um `down()` que devolvesse `null` apagaria datas legítimas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('company_plans')
            ->whereNull('starts_at')
            ->select(['id', 'created_at'])
            ->chunkById(500, function ($plans): void {
                foreach ($plans as $plan) {
                    DB::table('company_plans')
                        ->where('id', $plan->id)
                        ->update([
                            'starts_at' => Date::parse($plan->created_at ?? now())->toDateString(),
                        ]);
                }
            });
    }

    public function down(): void {}
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

/**
 * Preenche a âncora de cota das assinaturas que já existiam antes da coluna.
 *
 * Usa `created_at` por ausência de registro de ativação nas linhas antigas, e alcança
 * **somente** as já ativas. Carimbar uma linha ainda `pending` fixaria a âncora numa
 * data anterior ao pagamento, e o observer, vendo a coluna preenchida, não a corrigiria
 * na ativação — que é justamente o defeito que esta coluna existe para evitar. O que
 * não for carimbado aqui é carimbado pelo observer quando a assinatura ativar.
 *
 * Sobre o filtro: Barte e Stripe compartilham esta tabela e a coluna `stripe_status`,
 * com vocabulários diferentes (`pending|active|defaulter|inactive` contra
 * `active|trialing|past_due|canceled|incomplete|...`). `active` é o único valor comum
 * aos dois, e é o mesmo que `User::activeSubscription()` já usa para decidir quem tem
 * cota — então filtrar por ele mantém as duas leituras de acordo.
 *
 * Irreversível por desenho: depois do update não há como distinguir quais linhas eram
 * nulas, então um `down()` que devolvesse `null` apagaria âncoras legítimas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('billing_subscriptions')
            ->whereNull('quota_anchor_at')
            ->whereNotIn('stripe_status', Subscription::STATUSES_NEVER_ACTIVATED)
            ->update(['quota_anchor_at' => DB::raw('created_at')]);
    }

    public function down(): void {}
};

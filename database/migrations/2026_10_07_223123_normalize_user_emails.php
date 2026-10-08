<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use TresPontosTech\User\Support\EmailAddress;

return new class extends Migration
{
    /**
     * Converte para a forma canônica (sem espaços, minúsculo) os e-mails gravados antes do
     * mutator do User (#291). Em 2026-10-07 havia 1 caso em 182 usuários e nenhuma colisão.
     *
     * Se a forma canônica já pertencer a outra conta, a linha fica como está e a colisão
     * vai para o log: são duas contas da mesma pessoa, e juntá-las (encontros, créditos,
     * assinatura) é decisão manual. O deploy não falha por isso.
     */
    public function up(): void
    {
        DB::table('users')
            ->select(['id', 'email'])
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                $normalized = EmailAddress::normalize($user->email);

                if ($normalized === $user->email) {
                    return;
                }

                $collision = DB::table('users')
                    ->where('email', $normalized)
                    ->where('id', '!=', $user->id)
                    ->value('id');

                if ($collision !== null) {
                    Log::warning('E-mail não normalizado: a forma canônica já pertence a outra conta.', [
                        'user_id' => $user->id,
                        'colliding_user_id' => $collision,
                        'email' => $normalized,
                    ]);

                    return;
                }

                DB::table('users')->where('id', $user->id)->update(['email' => $normalized]);
            });
    }

    /**
     * Irreversível: a capitalização original não é guardada, e qualquer capitalização
     * continua entrando depois desta mudança.
     */
    public function down(): void {}
};

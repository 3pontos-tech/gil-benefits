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
     * mutator do User (#291). Depois desta mudança, login e "esqueci a senha" só encontram a
     * forma canônica, então uma conta ativa que ficar fora dela perde o acesso.
     *
     * Por isso as contas ativas vão primeiro, e o índice único de `users.email` (que vale também
     * para contas apagadas) decide o resto:
     * - forma canônica livre: grava;
     * - presa por uma conta apagada: o e-mail da apagada vira um marcador único e a ativa fica com
     *   a forma canônica; o e-mail original da apagada vai para o log;
     * - presa por outra conta ativa: as duas ficam como estão e a colisão vai para o log, porque
     *   são duas contas da mesma pessoa e juntá-las (encontros, créditos, assinatura) é decisão
     *   manual;
     * - a conta fora do padrão é a apagada: fica como está, já que não entra mais.
     *
     * O deploy não falha por nenhum desses casos.
     */
    public function up(): void
    {
        DB::table('users')
            ->select(['id', 'email', 'deleted_at'])
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->get()
            ->each(function (object $user): void {
                $normalized = EmailAddress::normalize($user->email);

                if ($normalized === $user->email) {
                    return;
                }

                $collision = DB::table('users')
                    ->select(['id', 'email', 'deleted_at'])
                    ->where('email', $normalized)
                    ->where('id', '!=', $user->id)
                    ->first();

                if ($collision !== null && $user->deleted_at !== null) {
                    return;
                }

                if ($collision !== null && $collision->deleted_at === null) {
                    Log::warning('E-mail não normalizado: a forma canônica já pertence a outra conta.', [
                        'user_id' => $user->id,
                        'colliding_user_id' => $collision->id,
                        'email' => $normalized,
                    ]);

                    return;
                }

                if ($collision !== null) {
                    $marker = sprintf('%s.deleted-%s', $collision->email, $collision->id);

                    DB::table('users')->where('id', $collision->id)->update(['email' => $marker]);

                    Log::warning('E-mail liberado de uma conta apagada para a conta ativa.', [
                        'user_id' => $user->id,
                        'deleted_user_id' => $collision->id,
                        'original_email' => $collision->email,
                        'marker' => $marker,
                    ]);
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

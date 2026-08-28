<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registra o último acesso do usuário (FLM-41, decisão D-09).
 *
 * Nenhuma métrica do cockpit lê esta coluna ainda: hoje "usuário ativo" sai de
 * proxies (e-mail verificado e consultoria no período), que são os mesmos que o
 * painel da empresa já usa. A coluna entra agora porque acesso é o único sinal
 * que não pode ser reconstruído depois — sem ela, o histórico começa no dia em
 * que alguém decidir que precisa dele.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['last_login_at']);
            $table->dropColumn('last_login_at');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Um token de push por sessão do app. A FK para o token do Sanctum é a regra: sair, trocar a
     * senha, entrar de novo no mesmo aparelho e o prune apagam o token de acesso com delete em
     * massa, sem eventos, e o banco leva o token de push junto.
     */
    public function up(): void
    {
        Schema::create('push_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->index()->constrained()->cascadeOnDelete();
            $table->foreignId('personal_access_token_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform', 16);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_tokens');
    }
};

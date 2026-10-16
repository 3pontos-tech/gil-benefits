<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O índice único em `user_id` que a migration original pretendia: o `->unique()` encadeado
     * depois do `constrained()` cai na definição da chave estrangeira e não gera índice. Com ele,
     * dois salvamentos simultâneos da primeira resposta não criam duas linhas: o
     * `updateOrCreate` que perde a corrida atualiza a linha do outro.
     */
    public function up(): void
    {
        Schema::table('user_anamneses', function (Blueprint $table): void {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_anamneses', function (Blueprint $table): void {
            $table->dropUnique(['user_id']);
        });
    }
};

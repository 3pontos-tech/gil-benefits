<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * As respostas passam a aceitar nulo para o app salvar a anamnese aos poucos, campo a
     * campo. "Completa" continua sendo os cinco preenchidos (UserAnamnese::isComplete()).
     */
    public function up(): void
    {
        Schema::table('user_anamneses', function (Blueprint $table): void {
            $table->string('life_moment')->nullable()->change();
            $table->text('main_motivation')->nullable()->change();
            $table->text('money_relationship')->nullable()->change();
            $table->text('plans_monthly_expenses')->nullable()->change();
            $table->text('tried_financial_strategies')->nullable()->change();
        });
    }

    /**
     * Só volta a exigir as respostas se nenhuma anamnese estiver incompleta; senão falha
     * explicando, em vez de apagar respostas parciais.
     */
    public function down(): void
    {
        $incomplete = DB::table('user_anamneses')
            ->where(fn ($query) => $query
                ->whereNull('life_moment')
                ->orWhereNull('main_motivation')
                ->orWhereNull('money_relationship')
                ->orWhereNull('plans_monthly_expenses')
                ->orWhereNull('tried_financial_strategies'))
            ->count();

        throw_if($incomplete > 0, RuntimeException::class, sprintf(
            'Há %d anamnese(s) incompleta(s); complete ou remova antes de voltar as colunas para NOT NULL.',
            $incomplete,
        ));

        Schema::table('user_anamneses', function (Blueprint $table): void {
            $table->string('life_moment')->nullable(false)->change();
            $table->text('main_motivation')->nullable(false)->change();
            $table->text('money_relationship')->nullable(false)->change();
            $table->text('plans_monthly_expenses')->nullable(false)->change();
            $table->text('tried_financial_strategies')->nullable(false)->change();
        });
    }
};

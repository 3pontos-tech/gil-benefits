<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Custo de repasse por consultoria (FLM-41, STORY-239).
 *
 * Por consultoria e não por hora: o agendamento não guarda duração, e cada
 * consultoria consome exatamente um crédito — essa é a unidade natural do
 * modelo.
 *
 * Nulável de propósito. Hoje todos os consultores são do mesmo parceiro, então
 * o valor vem do padrão em `config/billing.php` e a coluna fica vazia. Quando
 * entrarem outros parceiros, basta preencher aqui, sem migration nova.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultants', function (Blueprint $table): void {
            $table->unsignedInteger('cost_per_appointment_cents')->nullable()->after('crm_id');
        });
    }

    public function down(): void
    {
        Schema::table('consultants', function (Blueprint $table): void {
            $table->dropColumn('cost_per_appointment_cents');
        });
    }
};

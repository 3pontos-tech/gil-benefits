<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Os tickets que a Expo devolve a cada envio aceito. O recibo de cada um só sai depois, e é
     * ele que diz se o aparelho ainda existe; a Expo apaga os recibos em 24 h.
     */
    public function up(): void
    {
        Schema::create('push_tickets', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignUuid('push_token_id')->index()->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_tickets');
    }
};

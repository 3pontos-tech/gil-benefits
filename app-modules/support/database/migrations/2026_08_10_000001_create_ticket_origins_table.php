<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where a ticket came in from, for the ones that did not start on the platform.
     *
     * Deliberately a sibling table rather than columns on `support_tickets`: only
     * integration-born tickets have an origin, and the hot table stays free of
     * integration concerns. It is also not `ticket_destinations` — that one records
     * where a ticket was *sent* (sector board, sector inbox) and every column of it
     * is about delivery (channel recipient, sender, sent/failed status).
     *
     * The composite unique is what makes intake idempotent: a source replaying the
     * same external_reference hits the constraint instead of opening a second ticket.
     */
    public function up(): void
    {
        Schema::create('ticket_origins', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            // One origin per ticket.
            $table->foreignUuid('support_ticket_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('source');
            $table->string('external_reference');

            $table->timestamps();

            $table->unique(['source', 'external_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_origins');
    }
};

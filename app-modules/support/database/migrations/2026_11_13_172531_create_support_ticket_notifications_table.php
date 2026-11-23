<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->string('event');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('message');
            $table->string('status')->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('error')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampsTz();

            $table->index(['support_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_notifications');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_grants', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('discount_id')->constrained('discounts')->restrictOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->index();
            $table->string('name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('source');
            $table->string('external_ref');
            $table->text('tax_id')->nullable();
            $table->string('consent_term_version')->nullable();
            $table->timestampTz('consent_accepted_at')->nullable();
            $table->unsignedTinyInteger('failed_tax_id_attempts')->default(0);
            $table->timestampTz('expires_at');
            $table->timestampTz('claimed_at')->nullable();
            $table->string('plan_slug')->nullable();
            $table->timestampTz('first_redeemed_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->timestamps();

            $table->unique(['discount_id', 'external_ref']);
            $table->unique(['discount_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_grants');
    }
};

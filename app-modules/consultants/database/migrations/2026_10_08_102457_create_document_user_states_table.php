<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TresPontosTech\Consultants\Models\Document;

return new class extends Migration
{
    /**
     * Estado de cada material para cada pessoa: quando viu pela primeira vez e se favoritou.
     */
    public function up(): void
    {
        Schema::create('document_user_states', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignIdFor(Document::class, 'document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('favorited_at')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_user_states');
    }
};

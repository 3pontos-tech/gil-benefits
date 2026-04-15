<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('appointment_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();
            $table->longText('content')->nullable();
            $table->longText('internal_summary')->nullable();
            $table->string('model_used')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};

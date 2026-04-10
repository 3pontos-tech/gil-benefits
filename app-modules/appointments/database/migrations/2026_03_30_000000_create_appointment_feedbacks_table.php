<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_feedbacks', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->index()->constrained()->cascadeOnDelete();
            $table->tinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_feedbacks');
    }
};

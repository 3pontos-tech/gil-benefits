<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_shares', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Document::class, 'document_id')->constrained('documents');
            $table->foreignIdFor(Consultant::class, 'consultant_id')->constrained('consultants');
            $table->foreignUuid('employee_id')->constrained('users');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['document_id', 'consultant_id', 'employee_id'], 'document_shares_unique');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_shares');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TresPontosTech\Consultants\Document;
use TresPontosTech\Consultants\Models\Consultant;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_share', function (Blueprint $table): void {
            $table->foreignIdFor(Document::class, 'document_id')->constrained('documents');
            $table->foreignIdFor(Consultant::class, 'consultant_id')->constrained('consultants');
            $table->foreignUuid('employee_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_shares');
    }
};

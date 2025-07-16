<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_profile', function (Blueprint $table) {
            $table->foreignUuid('user_id')->primary()->constrained('users');
            $table->foreignUuid('company_id')->constrained('companies');
            $table->string('document_id', 50)->nullable();
            $table->string('tax_id', 14)->unique()->nullable();
            $table->timestamps();
        });
    }
};

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
        Schema::create('company_employees', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users');
            $table->foreignUuid('company_id')->constrained('companies');
            $table->primary(['user_id', 'company_id']);
            $table->timestamps();
        });
    }
};

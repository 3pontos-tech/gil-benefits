<?php

use App\Models\Plans\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignIdFor(Plan::class)->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tax_id');
            $table->timestamps();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('price');
            $table->string('type')->comment("'monthly', 'annual'");
            $table->integer('hours_included');
            $table->text('description');
            $table->timestamps();
        });
    }
};

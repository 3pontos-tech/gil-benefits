<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans');
            $table->string('name');
            $table->integer('price');
            $table->string('type');
            $table->integer('quantity');
            $table->timestamps();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_plans', function (Blueprint $table): void {
            $table->unique(['provider', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('billing_plans', function (Blueprint $table): void {
            $table->dropUnique(['provider', 'slug']);
        });
    }
};

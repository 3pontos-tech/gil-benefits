<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropMorphs('notifiable');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->uuidMorphs('notifiable');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropMorphs('notifiable');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->morphs('notifiable');
        });
    }
};

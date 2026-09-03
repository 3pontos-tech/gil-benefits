<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_credits', function (Blueprint $table): void {
            $table->foreignUuid('grant_id')
                ->nullable()
                ->after('company_id')
                ->constrained('credit_grants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_credits', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('grant_id');
        });
    }
};

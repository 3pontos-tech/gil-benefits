<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table): void {
            $table->string('tax_id_fingerprint', 67)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table): void {
            $table->dropIndex(['tax_id_fingerprint']);
            $table->dropColumn('tax_id_fingerprint');
        });
    }
};

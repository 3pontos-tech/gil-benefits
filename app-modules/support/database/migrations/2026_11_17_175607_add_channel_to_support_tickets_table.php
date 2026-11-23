<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Existing tickets are backfilled as panel_app. No default stays on the column
     * afterwards, so a new opening point has to declare its channel.
     */
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->string('channel')->nullable()->after('status');
        });

        DB::table('support_tickets')->whereNull('channel')->update(['channel' => 'panel_app']);

        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->string('channel')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->dropColumn('channel');
        });
    }
};

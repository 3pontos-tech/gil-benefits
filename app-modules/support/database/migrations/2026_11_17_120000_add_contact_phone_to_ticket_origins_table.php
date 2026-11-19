<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The phone the ticket was opened from, which is where its updates must go back to.
     * It lives on the ticket rather than on the account because a person can open each
     * ticket from a different number, and the account phone may be another one entirely.
     */
    public function up(): void
    {
        Schema::table('ticket_origins', function (Blueprint $table): void {
            $table->string('contact_phone', 20)->nullable()->after('external_reference');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_origins', function (Blueprint $table): void {
            $table->dropColumn('contact_phone');
        });
    }
};

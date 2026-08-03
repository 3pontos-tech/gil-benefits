<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;

/**
 * History entries used to come only from the admin panel, so the author column was named
 * admin_id. Clients now write their own entries (rescheduling), which makes the name wrong
 * and leaves no way to tell the two sides apart. Rename the column and add the discriminator.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_histories', function (Blueprint $table): void {
            $table->renameColumn('admin_id', 'actor_id');
        });

        Schema::table('appointment_histories', function (Blueprint $table): void {
            $table->string('actor_type')->default(AppointmentHistoryActor::Admin->value);
        });

        // Everything written before this migration came from the admin edit screen. The column
        // default already covers the existing rows; stating it here keeps the intent on record
        // even if the default is dropped later.
        DB::table('appointment_histories')->update(['actor_type' => AppointmentHistoryActor::Admin->value]);
    }

    public function down(): void
    {
        Schema::table('appointment_histories', function (Blueprint $table): void {
            $table->dropColumn('actor_type');
        });

        Schema::table('appointment_histories', function (Blueprint $table): void {
            $table->renameColumn('actor_id', 'admin_id');
        });
    }
};

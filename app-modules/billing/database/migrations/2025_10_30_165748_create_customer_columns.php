<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('stripe_id')->after('id')->nullable()->index();
            $table->string('pm_type')->after('pm_provider_id')->nullable();
            $table->string('pm_last_four', 4)->after('pm_provider_id')->nullable();
            $table->timestamp('trial_ends_at')->after('pm_last_four')->nullable();
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->string('stripe_id')->after('tax_id')->nullable()->index();
            $table->string('pm_type')->after('pm_provider_id')->nullable();
            $table->string('pm_last_four', 4)->after('pm_provider_id')->nullable();
            $table->timestamp('trial_ends_at')->after('pm_last_four')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex([
                'pm_provider',
                'pm_provider_id',
            ]);

            $table->dropColumn([
                'stripe_id',
                'pm_type',
                'pm_last_four',
                'trial_ends_at',
            ]);
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropIndex([
                'pm_provider',
                'pm_provider_id',
            ]);

            $table->dropColumn([
                'stripe_id',
                'pm_type',
                'pm_last_four',
                'trial_ends_at',
            ]);
        });
    }
};

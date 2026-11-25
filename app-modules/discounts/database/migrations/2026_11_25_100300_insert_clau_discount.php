<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('discounts')->insert([
            'id' => (string) Str::uuid(),
            'slug' => 'clau-diagnostico',
            'name' => 'Convite Clau: diagnóstico concluído',
            'percent_off' => 30,
            'audience' => 'standalone',
            'claim_window_days' => 180,
            'locks_plan' => true,
            'max_redemptions_per_customer' => null,
            'active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('discounts')->where('slug', 'clau-diagnostico')->delete();
    }
};

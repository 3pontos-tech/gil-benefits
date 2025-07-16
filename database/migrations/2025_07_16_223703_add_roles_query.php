<?php

use App\Enums\RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (RoleEnum::cases() as $role) {
            Role::create(['name' => $role->value]);
        }
    }
};

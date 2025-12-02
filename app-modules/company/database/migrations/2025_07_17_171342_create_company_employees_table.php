<?php

use App\Models\Users\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TresPontosTech\Company\Models\Company;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_employees', function (Blueprint $table): void {
            $table->foreignIdFor(Company::class);
            $table->foreignIdFor(User::class);
            $table->string('role')->comment("'owner', 'manager', 'employee'")->nullable();
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }
};

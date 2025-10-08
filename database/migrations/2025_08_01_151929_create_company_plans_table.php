<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Plans\Models\Item;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_plans', function (Blueprint $table): void {
            $table->foreignIdFor(Company::class);
            $table->foreignIdFor(Item::class);
            $table->date('subscription_starting_at')->nullable();
            $table->string('status')->comment("'active', 'inactive'");
            $table->softDeletes();
            $table->timestamps();
        });
    }
};

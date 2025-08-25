<?php

use App\Models\Companies\Company;
use App\Models\Consultant;
use App\Models\Users\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table): void {
            $table->id();
            $table->string('code');
            $table->foreignIdFor(Company::class);
            $table->foreignIdFor(Consultant::class)->nullable();
            $table->foreignIdFor(User::class)->nullable();
            $table->string('status')->comment("'pending', 'active', 'used', 'expired'");
            $table->timestamp('valid_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['code', 'company_id']);
        });
    }
};

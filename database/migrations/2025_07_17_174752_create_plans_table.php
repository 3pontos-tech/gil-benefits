<?php

use App\Models\Companies\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignIdFor(Company::class)->nullable();
            $table->string('name');
            $table->integer('suggested_employees_count');
            $table->integer('hours_included');
            $table->text('description');
            $table->softDeletes();
            $table->timestamps();
        });
    }
};

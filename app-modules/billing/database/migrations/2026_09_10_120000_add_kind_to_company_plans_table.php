<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TresPontosTech\Billing\Core\Enums\CompanyPlanKindEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_plans', function (Blueprint $table): void {
            $table->string('kind')
                ->default(CompanyPlanKindEnum::MonthlyQuota->value)
                ->after('plan_id');
        });

        Schema::table('company_plans', function (Blueprint $table): void {
            $table->unsignedTinyInteger('monthly_appointments_per_employee')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('company_plans', function (Blueprint $table): void {
            $table->dropColumn('kind');
        });

        Schema::table('company_plans', function (Blueprint $table): void {
            $table->unsignedTinyInteger('monthly_appointments_per_employee')->default(1)->change();
        });
    }
};

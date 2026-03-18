<?php

namespace TresPontosTech\Billing\Core\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use TresPontosTech\Billing\Database\Factories\CompanyPlanFactory;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Company\Models\Company;

class CompanyPlan extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $table = 'company_plans';

    protected $fillable = [
        'company_id',
        'plan_id',
        'seats',
        'monthly_appointments_per_employee',
        'status',
        'starts_at',
        'ends_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => CompanyPlanStatusEnum::class,
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    protected static function newFactory(): CompanyPlanFactory
    {
        return CompanyPlanFactory::new();
    }
}

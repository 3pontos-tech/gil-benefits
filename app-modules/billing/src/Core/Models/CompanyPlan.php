<?php

namespace TresPontosTech\Billing\Core\Models;

use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Database\Factories\CompanyPlanFactory;
use TresPontosTech\Company\Models\Company;

/**
 * @property string $id
 * @property string $company_id
 * @property int $plan_id
 * @property int $seats
 * @property int $monthly_appointments_per_employee
 * @property CompanyPlanStatusEnum $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[UseFactory(CompanyPlanFactory::class)]
class CompanyPlan extends Model
{
    /** @use HasFactory<CompanyPlanFactory> */
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
            'monthly_appointments_per_employee' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}

<?php

namespace App\Models\Plans;

use App\Enums\PlanTypeEnum;
use App\Models\Companies\Company;
use App\Policies\Plans\PlanPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UsePolicy(PlanPolicy::class)]
class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'type',
        'hours_included',
        'description',
        'renewal_date',
    ];

    protected function casts(): array
    {
        return [
            'type' => PlanTypeEnum::class,
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_plans', 'plan_id', 'company_id')->withTimestamps();
    }
}

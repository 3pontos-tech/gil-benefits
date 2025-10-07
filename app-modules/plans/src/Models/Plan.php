<?php

namespace TresPontosTech\Plans\Models;

use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Plans\Database\Factories\PlanFactory;
use TresPontosTech\Plans\Policies\PlanPolicy;

#[UsePolicy(PlanPolicy::class)]
class Plan extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'hours_included',
        'description',
        'suggested_employees_count',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_plans', 'item_id', 'company_id')->withTimestamps();
    }

    protected static function newFactory(): PlanFactory
    {
        return PlanFactory::new();
    }
}

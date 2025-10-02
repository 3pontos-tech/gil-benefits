<?php

namespace App\Models\Plans;

use App\Enums\PlanTypeEnum;
use App\Policies\Plans\ItemPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsePolicy(ItemPolicy::class)]
class Item extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'plan_items';

    protected $fillable = [
        'plan_id',
        'price',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => PlanTypeEnum::class,
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}

<?php

namespace App\Models\Plans;

use App\Policies\Plans\ItemPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UsePolicy(ItemPolicy::class)]
class Item extends Model
{
    use HasFactory;

    protected $table = 'plan_items';

    protected $fillable = [
        'plan_id',
        'name',
        'price',
        'type',
        'quantity',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}

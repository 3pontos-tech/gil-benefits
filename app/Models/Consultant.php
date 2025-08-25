<?php

namespace App\Models;

use App\Policies\ConsultantPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsePolicy(ConsultantPolicy::class)]
class Consultant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'description',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }
}

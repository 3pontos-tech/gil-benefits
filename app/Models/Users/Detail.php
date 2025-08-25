<?php

namespace App\Models\Users;

use App\Policies\Users\DetailPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UsePolicy(DetailPolicy::class)]
class Detail extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'user_details';

    protected $fillable = [
        'user_id',
        'company_id',
        'document_id',
        'tax_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

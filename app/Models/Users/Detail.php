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
        'phone_number',
        'company_id',
        'document_id',
        'tax_id',
        'integration_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\TresPontosTech\Company\Models\Company::class);
    }
}

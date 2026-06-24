<?php

namespace App\Models\Users;

use App\Policies\Users\DetailPolicy;
use Database\Factories\Users\DetailFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $user_id
 * @property string $company_id
 * @property string|null $phone_number
 * @property string|null $integration_id
 * @property string $document_id
 * @property string $tax_id
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[UseFactory(DetailFactory::class)]
#[UsePolicy(DetailPolicy::class)]
class Detail extends Model
{
    /** @use HasFactory<DetailFactory> */
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

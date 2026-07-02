<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use TresPontosTech\Billing\Database\Factories\CreditGrantFactory;
use TresPontosTech\Company\Models\Company;

/**
 * Audit record of an extra-credit grant performed by a Flamma admin.
 * One grant produces N {@see UserCredit} rows (linked via grant_id).
 *
 * @property string $id
 * @property string|null $admin_user_id
 * @property string $company_id
 * @property string|null $target_user_id
 * @property int $quantity
 * @property string $justification
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[UseFactory(CreditGrantFactory::class)]
class CreditGrant extends Model
{
    /** @use HasFactory<CreditGrantFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'admin_user_id',
        'company_id',
        'target_user_id',
        'quantity',
        'justification',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    /**
     * @return HasMany<UserCredit, $this>
     */
    public function userCredits(): HasMany
    {
        return $this->hasMany(UserCredit::class, 'grant_id');
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Models;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use TresPontosTech\PanelAdmin\Database\Factories\ImpersonationLogFactory;

/**
 * @property int $id
 * @property string $admin_id
 * @property string $impersonated_user_id
 * @property string|null $ip_address
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(ImpersonationLogFactory::class)]
class ImpersonationLog extends Model
{
    /** @use HasFactory<ImpersonationLogFactory> */
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'impersonated_user_id',
        'ip_address',
        'started_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id')->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function impersonatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonated_user_id')->withTrashed();
    }
}

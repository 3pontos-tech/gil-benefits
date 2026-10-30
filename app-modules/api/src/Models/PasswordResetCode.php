<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Models;

use App\Models\Users\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use TresPontosTech\Api\Database\Factories\PasswordResetCodeFactory;

/**
 * Uma linha por pessoa com o estado da recuperação pelo app: o código pendente, o token que ele
 * vira e o contador de erros da conta. É tabela, e não cache, para esse estado não sumir num
 * flush ou numa expulsão do Redis. Um reset por qualquer caminho apaga a linha.
 *
 * @property string $id
 * @property string $user_id
 * @property string|null $code_hash
 * @property int $code_attempts
 * @property int $failed_attempts
 * @property Carbon|null $code_sent_at
 * @property Carbon|null $code_expires_at
 * @property string|null $reset_token_hash
 * @property Carbon|null $reset_token_expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[UseFactory(PasswordResetCodeFactory::class)]
final class PasswordResetCode extends Model
{
    /** @use HasFactory<PasswordResetCodeFactory> */
    use HasFactory;

    use HasUuids;
    use Prunable;

    protected $table = 'password_reset_codes';

    protected $fillable = [
        'user_id',
        'code_hash',
        'code_attempts',
        'failed_attempts',
        'code_sent_at',
        'code_expires_at',
        'reset_token_hash',
        'reset_token_expires_at',
    ];

    protected $hidden = [
        'code_hash',
        'reset_token_hash',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * O bloqueio só sai com um reset bem-sucedido, por qualquer caminho: pedir outro código não o
     * zera (NIST 800-63B). O link do site continua funcionando para quem chegou aqui.
     */
    public function isLockedOut(): bool
    {
        return $this->failed_attempts >= (int) config('api.password_reset.max_failed_attempts_per_account');
    }

    public function hasActiveCode(CarbonInterface $now): bool
    {
        return $this->code_hash !== null && $this->code_expires_at?->isAfter($now) === true;
    }

    public function hasActiveResetToken(CarbonInterface $now): bool
    {
        return $this->reset_token_hash !== null && $this->reset_token_expires_at?->isAfter($now) === true;
    }

    /**
     * Antes da espera, o pedido responde igual e não manda nada: a pessoa não recebe uma fila de
     * e-mails, e o código que acabou de chegar continua valendo.
     */
    public function canSendCode(CarbonInterface $now): bool
    {
        return ! $this->code_sent_at instanceof CarbonInterface
            || $this->code_sent_at->lte($now->copy()->subSeconds((int) config('api.password_reset.resend_cooldown_seconds')));
    }

    /**
     * Fica a linha que ainda guarda alguma coisa: um código ou um token valendo, ou erros da conta.
     *
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        $now = now();

        return self::query()
            ->where('failed_attempts', 0)
            ->where(fn (Builder $query): Builder => $query->whereNull('code_hash')->orWhere('code_expires_at', '<=', $now))
            ->where(fn (Builder $query): Builder => $query->whereNull('reset_token_hash')->orWhere('reset_token_expires_at', '<=', $now));
    }

    protected function casts(): array
    {
        return [
            'code_attempts' => 'integer',
            'failed_attempts' => 'integer',
            'code_sent_at' => 'datetime',
            'code_expires_at' => 'datetime',
            'reset_token_expires_at' => 'datetime',
        ];
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Support;

use Illuminate\Support\Str;
use SensitiveParameter;

/**
 * Código e token da recuperação pelo app: gerados aqui e guardados só como HMAC-SHA256 com a
 * `app.key`, comparados com `hash_equals`, como o broker do Laravel faz com o token do link. Um
 * bcrypt seria só mais lento, sem ganho: o que protege o código de 6 dígitos é o limite de
 * tentativas, não o custo do hash.
 */
final class PasswordResetSecret
{
    public static function code(): string
    {
        return str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
    }

    public static function token(): string
    {
        return Str::random(64);
    }

    public static function hash(#[SensitiveParameter] string $secret): string
    {
        return hash_hmac('sha256', $secret, (string) config('app.key'));
    }

    public static function matches(?string $hash, #[SensitiveParameter] string $secret): bool
    {
        return $hash !== null && hash_equals($hash, self::hash($secret));
    }
}

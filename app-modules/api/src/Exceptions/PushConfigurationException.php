<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Exceptions;

use RuntimeException;
use TresPontosTech\Api\Enums\ExpoPushError;

/**
 * Um erro de push que não é do aparelho, e sim da configuração (ExpoPushError::isConfiguration).
 * Ele quebra todo envio daquela plataforma, então é relatado (o Nightwatch o captura) em vez de
 * só ir para o log. Leva só o código da Expo, porque a mensagem dela repete o token.
 */
final class PushConfigurationException extends RuntimeException
{
    public static function from(ExpoPushError $error, string $stage): self
    {
        return new self(sprintf('Expo push %s error: %s', $stage, $error->value));
    }
}

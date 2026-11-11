<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Exceptions;

use RuntimeException;
use TresPontosTech\Api\Enums\ExpoPushError;

/**
 * Um erro de push que não é do aparelho, e sim da configuração: um código da Expo
 * (ExpoPushError::isConfiguration) ou o pedido inteiro recusado com 401/403. Ele quebra todo
 * envio, então é relatado (o Nightwatch o captura) em vez de só ir para o log. Leva só o código,
 * porque a mensagem da Expo repete o token.
 */
final class PushConfigurationException extends RuntimeException
{
    public static function from(ExpoPushError $error, string $stage): self
    {
        return new self(sprintf('Expo push %s error: %s', $stage, $error->value));
    }

    public static function fromStatus(int $status): self
    {
        return new self(sprintf('Expo push request refused: HTTP %d', $status));
    }
}

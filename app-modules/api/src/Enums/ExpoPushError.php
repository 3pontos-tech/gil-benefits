<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Enums;

/**
 * Os códigos de erro que a Expo devolve em `details.error`, no ticket e no recibo.
 *
 * @see https://docs.expo.dev/push-notifications/sending-notifications/#individual-errors
 */
enum ExpoPushError: string
{
    case DeviceNotRegistered = 'DeviceNotRegistered';
    case MessageTooBig = 'MessageTooBig';
    case MessageRateExceeded = 'MessageRateExceeded';
    case MismatchSenderId = 'MismatchSenderId';
    case InvalidCredentials = 'InvalidCredentials';

    /**
     * Erro da configuração, não de um aparelho: quebra todo envio daquela plataforma (chave FCM
     * errada ou revogada, `google-services.json` de outro projeto) ou é defeito nosso (mensagem
     * grande demais). Vai para o relato de erros, não só para o log.
     */
    public function isConfiguration(): bool
    {
        return in_array($this, [self::InvalidCredentials, self::MismatchSenderId, self::MessageTooBig], true);
    }
}

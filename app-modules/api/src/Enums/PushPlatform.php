<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Enums;

/**
 * O sistema do aparelho que registrou o token de push. A Expo entrega pelo FCM no Android e
 * pelo APNs no iOS; o valor serve para diagnóstico e para separar os envios no futuro.
 */
enum PushPlatform: string
{
    case Android = 'android';
    case Ios = 'ios';
}

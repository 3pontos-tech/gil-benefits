<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Support;

use Random\RandomException;

final class VoucherCodeGenerator
{
    public const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public const GROUP_SIZE = 4;

    public const GROUPS = 2;

    /**
     * @throws RandomException
     */
    public static function generate(): string
    {
        $groups = [];

        for ($group = 0; $group < self::GROUPS; ++$group) {
            $chars = '';

            for ($i = 0; $i < self::GROUP_SIZE; ++$i) {
                $chars .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }

            $groups[] = $chars;
        }

        return implode('-', $groups);
    }

    public static function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }
}

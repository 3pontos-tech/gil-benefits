<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Exceptions;

use RuntimeException;

class VoucherRedemptionException extends RuntimeException
{
    public static function codeNotFound(): self
    {
        return new self(__('vouchers::vouchers.errors.code_not_found'));
    }

    public static function codeExhausted(): self
    {
        return new self(__('vouchers::vouchers.errors.code_exhausted'));
    }

    public static function alreadyRedeemed(): self
    {
        return new self(__('vouchers::vouchers.errors.already_redeemed'));
    }

    public static function batchExpired(): self
    {
        return new self(__('vouchers::vouchers.errors.batch_expired'));
    }

    public static function programInactive(): self
    {
        return new self(__('vouchers::vouchers.errors.program_inactive'));
    }

    public static function notFromUserCompany(): self
    {
        return new self(__('vouchers::vouchers.errors.not_from_user_company'));
    }
}

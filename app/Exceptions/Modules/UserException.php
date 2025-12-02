<?php

declare(strict_types=1);

namespace App\Exceptions\Modules;

use App\Exceptions\BusinessLogicException;

class UserException extends BusinessLogicException
{
    protected function getDefaultErrorCode(): string
    {
        return 'USER_ERROR';
    }

    public static function userNotFound(mixed $identifier, array $context = []): static
    {
        return new static(
            "User not found with identifier: {$identifier}",
            404,
            null,
            array_merge($context, ['identifier' => $identifier])
        )->setErrorCode('USER_NOT_FOUND');
    }

    public static function emailAlreadyExists(string $email, array $context = []): static
    {
        return new static(
            "User with email {$email} already exists",
            409,
            null,
            array_merge($context, ['email' => $email])
        )->setErrorCode('EMAIL_ALREADY_EXISTS');
    }

    public static function cpfAlreadyExists(string $cpf, array $context = []): static
    {
        return new static(
            'User with CPF already exists',
            409,
            null,
            array_merge($context, ['cpf_hash' => hash('sha256', $cpf)])
        )->setErrorCode('CPF_ALREADY_EXISTS');
    }

    public static function invalidCredentials(array $context = []): static
    {
        return new static(
            'Invalid login credentials provided',
            401,
            null,
            $context
        )->setErrorCode('INVALID_CREDENTIALS')->setLogChannel('security');
    }

    public static function accountNotVerified(int $userId, array $context = []): static
    {
        return new static(
            'Account email verification required',
            403,
            null,
            array_merge($context, ['user_id' => $userId])
        )->setErrorCode('ACCOUNT_NOT_VERIFIED');
    }

    public static function accountSuspended(int $userId, string $reason, array $context = []): static
    {
        return new static(
            "Account suspended: {$reason}",
            403,
            null,
            array_merge($context, ['user_id' => $userId, 'reason' => $reason])
        )->setErrorCode('ACCOUNT_SUSPENDED')->setLogChannel('security');
    }

    public static function passwordResetFailed(string $reason, array $context = []): static
    {
        return new static(
            "Password reset failed: {$reason}",
            400,
            null,
            array_merge($context, ['reason' => $reason])
        )->setErrorCode('PASSWORD_RESET_FAILED');
    }

    public static function profileUpdateFailed(string $reason, array $context = []): static
    {
        return new static(
            "Profile update failed: {$reason}",
            400,
            null,
            array_merge($context, ['reason' => $reason])
        )->setErrorCode('PROFILE_UPDATE_FAILED');
    }
}

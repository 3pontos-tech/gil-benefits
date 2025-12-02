<?php

declare(strict_types=1);

namespace App\Exceptions\Modules;

use App\Exceptions\BusinessLogicException;

class CompanyException extends BusinessLogicException
{
    protected function getDefaultErrorCode(): string
    {
        return 'COMPANY_ERROR';
    }

    public static function companyNotFound(mixed $identifier, array $context = []): static
    {
        return new static(
            "Company not found with identifier: {$identifier}",
            404,
            null,
            array_merge($context, ['identifier' => $identifier])
        )->setErrorCode('COMPANY_NOT_FOUND');
    }

    public static function partnerCodeNotFound(string $partnerCode, array $context = []): static
    {
        return new static(
            'Invalid partner code provided',
            404,
            null,
            array_merge($context, ['partner_code_hash' => hash('sha256', $partnerCode)])
        )->setErrorCode('PARTNER_CODE_NOT_FOUND');
    }

    public static function partnerCodeAlreadyUsed(string $partnerCode, array $context = []): static
    {
        return new static(
            'Partner code has already been used',
            409,
            null,
            array_merge($context, ['partner_code_hash' => hash('sha256', $partnerCode)])
        )->setErrorCode('PARTNER_CODE_ALREADY_USED');
    }

    public static function employeeLimitExceeded(int $companyId, int $currentCount, int $limit, array $context = []): static
    {
        return new static(
            "Employee limit exceeded. Current: {$currentCount}, Limit: {$limit}",
            403,
            null,
            array_merge($context, [
                'company_id' => $companyId,
                'current_count' => $currentCount,
                'limit' => $limit,
            ])
        )->setErrorCode('EMPLOYEE_LIMIT_EXCEEDED');
    }

    public static function subscriptionRequired(int $companyId, string $feature, array $context = []): static
    {
        return new static(
            "Active subscription required to access feature: {$feature}",
            402,
            null,
            array_merge($context, [
                'company_id' => $companyId,
                'feature' => $feature,
            ])
        )->setErrorCode('SUBSCRIPTION_REQUIRED');
    }

    public static function tenantAccessDenied(int $companyId, int $userId, array $context = []): static
    {
        return new static(
            'User does not have access to company data',
            403,
            null,
            array_merge($context, [
                'company_id' => $companyId,
                'user_id' => $userId,
            ])
        )->setErrorCode('TENANT_ACCESS_DENIED')->setLogChannel('security');
    }

    public static function companySettingsUpdateFailed(string $reason, array $context = []): static
    {
        return new static(
            "Company settings update failed: {$reason}",
            400,
            null,
            array_merge($context, ['reason' => $reason])
        )->setErrorCode('SETTINGS_UPDATE_FAILED');
    }
}

<?php

declare(strict_types=1);

namespace App\Exceptions\Modules;

use App\Exceptions\BusinessLogicException;

class ConsultantException extends BusinessLogicException
{
    protected function getDefaultErrorCode(): string
    {
        return 'CONSULTANT_ERROR';
    }

    public static function consultantNotFound(mixed $identifier, array $context = []): static
    {
        return new static(
            "Consultant not found with identifier: {$identifier}",
            404,
            null,
            array_merge($context, ['identifier' => $identifier])
        )->setErrorCode('CONSULTANT_NOT_FOUND');
    }

    public static function consultantNotActive(int $consultantId, array $context = []): static
    {
        return new static(
            'Consultant is not active',
            403,
            null,
            array_merge($context, ['consultant_id' => $consultantId])
        )->setErrorCode('CONSULTANT_NOT_ACTIVE');
    }

    public static function availabilityConflict(\DateTimeInterface $startTime, \DateTimeInterface $endTime, array $context = []): static
    {
        return new static(
            "Consultant availability conflict between {$startTime->format('Y-m-d H:i:s')} and {$endTime->format('Y-m-d H:i:s')}",
            409,
            null,
            array_merge($context, [
                'start_time' => $startTime->format('Y-m-d H:i:s'),
                'end_time' => $endTime->format('Y-m-d H:i:s'),
            ])
        )->setErrorCode('AVAILABILITY_CONFLICT');
    }

    public static function profileIncomplete(int $consultantId, array $missingFields, array $context = []): static
    {
        return new static(
            'Consultant profile is incomplete',
            400,
            null,
            array_merge($context, [
                'consultant_id' => $consultantId,
                'missing_fields' => $missingFields,
            ])
        )->setErrorCode('PROFILE_INCOMPLETE');
    }

    public static function specialtyNotFound(string $specialty, array $context = []): static
    {
        return new static(
            "Consultant specialty not found: {$specialty}",
            404,
            null,
            array_merge($context, ['specialty' => $specialty])
        )->setErrorCode('SPECIALTY_NOT_FOUND');
    }

    public static function scheduleUpdateFailed(string $reason, array $context = []): static
    {
        return new static(
            "Schedule update failed: {$reason}",
            400,
            null,
            array_merge($context, ['reason' => $reason])
        )->setErrorCode('SCHEDULE_UPDATE_FAILED');
    }

    public static function certificationExpired(int $consultantId, string $certification, array $context = []): static
    {
        return new static(
            "Consultant certification has expired: {$certification}",
            403,
            null,
            array_merge($context, [
                'consultant_id' => $consultantId,
                'certification' => $certification,
            ])
        )->setErrorCode('CERTIFICATION_EXPIRED');
    }
}

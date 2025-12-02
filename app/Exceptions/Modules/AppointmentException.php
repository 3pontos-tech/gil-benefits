<?php

declare(strict_types=1);

namespace App\Exceptions\Modules;

use App\Exceptions\BusinessLogicException;

class AppointmentException extends BusinessLogicException
{
    protected function getDefaultErrorCode(): string
    {
        return 'APPOINTMENT_ERROR';
    }

    public static function invalidStatus(string $currentStatus, string $targetStatus, array $context = []): static
    {
        return new static(
            "Cannot transition appointment from {$currentStatus} to {$targetStatus}",
            400,
            null,
            array_merge($context, [
                'current_status' => $currentStatus,
                'target_status' => $targetStatus,
            ])
        )->setErrorCode('INVALID_STATUS_TRANSITION');
    }

    public static function slotNotAvailable(\DateTimeInterface $dateTime, array $context = []): static
    {
        return new static(
            "Appointment slot not available at {$dateTime->format('Y-m-d H:i:s')}",
            409,
            null,
            array_merge($context, ['requested_datetime' => $dateTime->format('Y-m-d H:i:s')])
        )->setErrorCode('SLOT_NOT_AVAILABLE');
    }

    public static function consultantNotAvailable(int $consultantId, \DateTimeInterface $dateTime, array $context = []): static
    {
        return new static(
            "Consultant {$consultantId} is not available at {$dateTime->format('Y-m-d H:i:s')}",
            409,
            null,
            array_merge($context, [
                'consultant_id' => $consultantId,
                'requested_datetime' => $dateTime->format('Y-m-d H:i:s'),
            ])
        )->setErrorCode('CONSULTANT_NOT_AVAILABLE');
    }

    public static function appointmentNotFound(int $appointmentId, array $context = []): static
    {
        return new static(
            "Appointment not found with ID: {$appointmentId}",
            404,
            null,
            array_merge($context, ['appointment_id' => $appointmentId])
        )->setErrorCode('APPOINTMENT_NOT_FOUND');
    }

    public static function cancellationNotAllowed(string $reason, array $context = []): static
    {
        return new static(
            "Appointment cancellation not allowed: {$reason}",
            403,
            null,
            array_merge($context, ['reason' => $reason])
        )->setErrorCode('CANCELLATION_NOT_ALLOWED');
    }

    public static function rescheduleNotAllowed(string $reason, array $context = []): static
    {
        return new static(
            "Appointment reschedule not allowed: {$reason}",
            403,
            null,
            array_merge($context, ['reason' => $reason])
        )->setErrorCode('RESCHEDULE_NOT_ALLOWED');
    }
}

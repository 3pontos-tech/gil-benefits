<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions;

use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;
use Throwable;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Mail\AppointmentRequestedAdminMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\DTOs\CreditDTO;
use TresPontosTech\Billing\Core\Events\Credit\CreditConsumed;

readonly class BookAppointmentAction
{
    public function handle(BookAppointmentDTO $payload): void
    {
        $user = User::query()->find($payload->userId);

        $hasMonthlyQuota = $user->monthly_appointments_left > 0;

        $appointment = $user->appointments()->create([
            ...$payload->jsonSerialize(),
            'status' => AppointmentStatus::Pending,
        ]);

        if (! $hasMonthlyQuota) {
            event(new CreditConsumed(new CreditDTO(
                holderId: $user->getKey(),
                appointmentId: $appointment->getKey(),
            )));
        }

        $this->notifyAdmins($appointment);
    }

    private function notifyAdmins(Appointment $appointment): void
    {
        $recipients = config('appointments.admin_notification_recipients', []);

        if (blank($recipients)) {
            return;
        }

        try {
            Mail::to($recipients)->queue(new AppointmentRequestedAdminMail($appointment));
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }
}

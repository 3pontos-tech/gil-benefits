<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions;

use App\Models\Users\User;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\AppointmentFeedbackException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;

final readonly class SubmitAppointmentFeedbackAction
{
    /**
     * Registra a avaliação do colaborador: só para consultoria concluída, uma vez por encontro,
     * nota de 1 a 5; comentário em branco é gravado como null.
     *
     * @throws AppointmentFeedbackException
     */
    public function handle(Appointment $appointment, User $user, int $rating, ?string $comment): AppointmentFeedback
    {
        throw_unless($appointment->status === AppointmentStatus::Completed, AppointmentFeedbackException::requiresCompletion());
        throw_if(filled($appointment->feedback), AppointmentFeedbackException::alreadyGiven());
        throw_unless(in_array($rating, range(1, 5), strict: true), AppointmentFeedbackException::invalidRating());

        return AppointmentFeedback::query()->create([
            'appointment_id' => $appointment->getKey(),
            'user_id' => $user->getKey(),
            'rating' => $rating,
            'comment' => blank($comment) ? null : $comment,
        ]);
    }
}

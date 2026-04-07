<?php

namespace TresPontosTech\Appointments\Actions;

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentStatus;

readonly class BookAppointmentAction
{
    public function __construct(
        private ValidateSlotAvailabilityAction $validateSlotAvailability,
    ) {}

    public function handle(BookAppointmentDTO $payload): void
    {
        DB::transaction(function () use ($payload): void {
            $this->validateSlotAvailability->handle($payload->appointmentAt);

            $user = User::query()->find($payload->userId);

            $user->appointments()->create([
                ...$payload->jsonSerialize(),
                'status' => AppointmentStatus::Pending,
            ]);
        });
    }
}

<?php

namespace App\Action\Appointments;

use App\Clients\HighLevelClient;
use App\Clients\Requests\CreateAppointmentDTO;
use App\Clients\Requests\UpsertOpportunityDTO;
use App\DTO\BookAppointmentDTO;
use App\Enums\AppointmentStatus;
use App\Models\Users\User;
use App\Models\Voucher;

readonly class BookAppointmentAction
{
    public function __construct(private readonly HighLevelClient $client) {}

    public function handle(
        BookAppointmentDTO $payload
    ): void {
        $user = User::query()->find($payload->userId);
        $voucher = Voucher::query()->find($payload->voucherId);

        $opportunityResponse = $this->client->upsertOpportunity(UpsertOpportunityDTO::make(
            $user->external_id,
            '[Test] ' . $payload->categoryType->value . ' - ' . $user->name
        ));

        $dto = CreateAppointmentDTO::make(
            'teste integração',
            $user->external_id,
            $payload->appointmentAt->toIso8601ZuluString(),
            $payload->appointmentAt->addHour()->toIso8601ZuluString(),
        );

        $schedule = $this->client->scheduleAppointment($dto);

        $user->appointments()->create([
            ...$payload->jsonSerialize(),
            'status' => AppointmentStatus::Pending,
            'external_opportunity_id' => $opportunityResponse->opportunity->id,
            'external_appointment_id' => $schedule->id,
        ]);

        $voucher->markAsUsed();
    }
}

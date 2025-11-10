<?php

namespace TresPontosTech\Appointments\Actions;

use App\Models\Users\User;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\IntegrationHighlevel\HighLevelClient;
use TresPontosTech\IntegrationHighlevel\Requests\CreateAppointmentDTO;
use TresPontosTech\IntegrationHighlevel\Requests\UpsertOpportunityDTO;

readonly class BookAppointmentAction
{
    public function __construct(private HighLevelClient $client) {}

    public function handle(
        BookAppointmentDTO $payload
    ): void {
        $user = User::query()->find($payload->userId);

        $opportunityResponse = $this->client->upsertOpportunity(UpsertOpportunityDTO::make(
            $user->external_id,
            $user->name . ' - ' . $payload->categoryType->value
        ));

        $dto = CreateAppointmentDTO::make(
            'Acompanhamento Financeiro - ' . $user->name,
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

    }
}

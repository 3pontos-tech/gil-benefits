<?php

namespace TresPontosTech\Appointments\Actions;

use App\Models\Users\User;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\IntegrationHighlevel\HighLevelClient;
use TresPontosTech\IntegrationHighlevel\Requests\CreateAppointmentDTO;
use TresPontosTech\IntegrationHighlevel\Requests\UpsertOpportunityDTO;

// use TresPontosTech\IntegrationMonday\Jobs\CreateMondayItemJob;

readonly class BookAppointmentAction
{
    public function __construct(
        private HighLevelClient $client,
    ) {}

    public function handle(
        BookAppointmentDTO $payload
    ): void {
        $user = User::query()->find($payload->userId);

        $this->client->upsertOpportunity(UpsertOpportunityDTO::make(
            $user->crm_id,
            $user->name . ' - ' . $payload->categoryType->value
        ));

        $dto = CreateAppointmentDTO::make(
            'Acompanhamento Financeiro - ' . $user->name,
            $user->crm_id,
            $payload->appointmentAt->toIso8601ZuluString(),
            $payload->appointmentAt->addHour()->toIso8601ZuluString(),
        );

        $this->client->scheduleAppointment($dto);

        $user->appointments()->create([
            ...$payload->jsonSerialize(),
            'status' => AppointmentStatus::Pending,
        ]);
    }
}

<?php

namespace TresPontosTech\Appointments\Actions;

use App\Models\Users\User;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\IntegrationHighlevel\HighLevelClient;
use TresPontosTech\IntegrationHighlevel\Requests\CreateAppointmentDTO;
use TresPontosTech\IntegrationHighlevel\Requests\UpsertOpportunityDTO;
use TresPontosTech\Vouchers\Models\Voucher;

readonly class BookAppointmentAction
{
    public function __construct(private HighLevelClient $client) {}

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

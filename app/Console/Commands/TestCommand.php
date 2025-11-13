<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use TresPontosTech\Billing\Core\Entities\PriceEntity;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;

class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(PlanRepository $planRepository): void
    {
        $plan = $planRepository->get('user');

        $proPlan = $plan->prices->first(fn (PriceEntity $price): bool => $price->type === 'pro');

        dd($proPlan);

        //        //        $upsertDTO = UpsertContactDTO::make(
        //        //            tenantName: '5pontos',
        //        //            fullName: 'Thales Popokas',
        //        //            email: 'popokas@5pontos.com',
        //        //            phone: '11999999551',
        //        //        );
        //        //
        //        //        $customer = $client->createContact($upsertDTO);
        //        //
        //        //        $this->info(sprintf('Customer created with ID %s', $customer->contactId));
        //        //        $this->info(sprintf(' > Is new:  %s', $customer->isNewContact ? 'yes' : 'no'));
        //        // //
        //        //        $dto = new UpsertOpportunityDTO(
        //        //            'miSaf2ppCkOQd6icQu9e',
        //        //            config('services.highlevel.location'),
        //        //            $customer->contactId,
        //        //            'Consulta Teste via API',
        //        //            'open',
        //        //            "dbb04329-05c5-4445-a3fb-e1874546f259",
        //        //            1,
        //        //            "KMUqrN9NgV5fMXNue1z0"
        //        //        );
        //        // //
        //        //        $response = $client->upsertOpportunity($dto);
        //
        //        $consultantId = 'KMUqrN9NgV5fMXNue1z0';
        //        $response = $client->getCalendarFreeSlots(FetchCalendarSlotsDTO::make(
        //            now(),
        //            now(),
        //            $consultantId
        //        ));
        //
        //        // Filter if the next slot has the availability minimum of 1h
        //        $firstSlot = collect($response[Date::now()->format('Y-m-d')])->first()[0];
        //
        //        $appointmentTime = Date::parse($firstSlot);
        //
        //        $dto = CreateAppointmentDTO::make(
        //            'teste integração',
        //            'IZtkYVpbP7JCgqjlzuAA',
        //            $appointmentTime->toIso8601ZuluString(),
        //            $appointmentTime->addHour()->toIso8601ZuluString(),
        //            'EWmwbQiyuqttgLJ8CUMk',
        //        );
        //
        //        $schedule = $client->scheduleAppointment($dto);
        //        dd($schedule);
    }
}

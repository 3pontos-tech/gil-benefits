<?php

namespace App\Console\Commands;

use App\Clients\HighLevelClient;
use App\Clients\Requests\UpsertContactDTO;
use App\Clients\Requests\UpsertOpportunityDTO;
use Illuminate\Console\Command;

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
    public function handle(HighLevelClient $client)
    {
        $upsertDTO = UpsertContactDTO::make(
            fullName: 'Thales Popokas',
            email: 'popokas@5pontos.com',
            phone: '11999999551',
        );

        $customer = $client->createContact($upsertDTO);

        $this->info(sprintf('Customer created with ID %s', $customer->contactId));
        $this->info(sprintf(' > Is new:  %s', $customer->isNewContact ? 'yes' : 'no'));

        $dto = new UpsertOpportunityDTO(
            'miSaf2ppCkOQd6icQu9e',
            config('services.highlevel.location'),
            $customer->contactId,
            'Consulta Teste via API',
            'open',
            "dbb04329-05c5-4445-a3fb-e1874546f259",
            1,
            "KMUqrN9NgV5fMXNue1z0"
        );

        $response = $client->upsertOpportunity($dto);

        dd($response);

    }
}

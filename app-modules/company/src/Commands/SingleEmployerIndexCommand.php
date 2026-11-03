<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Commands;

use Illuminate\Console\Command;
use TresPontosTech\Company\Actions\EnsureSingleEmployerIndex;

class SingleEmployerIndexCommand extends Command
{
    protected $signature = 'company:single-employer-index';

    protected $description = 'Creates the one-active-employer index on company_employees once the default company exists';

    public function handle(EnsureSingleEmployerIndex $ensure): int
    {
        if (! $ensure->handle()) {
            $this->warn('Default company not found; index not created. Seed it and run again.');

            return self::FAILURE;
        }

        $this->info(sprintf('Index %s is in place.', EnsureSingleEmployerIndex::INDEX));

        return self::SUCCESS;
    }
}

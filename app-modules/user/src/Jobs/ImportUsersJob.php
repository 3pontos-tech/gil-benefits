<?php

namespace TresPontosTech\User\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\Actions\PersistImportedUsersAction;

class ImportUsersJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public readonly Collection $rows,
        public readonly string $companyId,
        public readonly string $userId,
    ) {}

    public function handle(PersistImportedUsersAction $persister): void
    {
        $company = Company::query()->findOrFail($this->companyId);
        $persister->execute($this->rows, $company);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ImportUsersJob falhou', [
            'company_id' => $this->companyId,
            'error' => $exception->getMessage(),
        ]);
    }
}

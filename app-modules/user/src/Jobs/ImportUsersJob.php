<?php

namespace TresPontosTech\User\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\Actions\ParseUsersFromFileAction;
use TresPontosTech\User\Actions\PersistImportedUsersAction;

class ImportUsersJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public readonly string $storagePath,
        public readonly string $fileExtension,
        public readonly string $companyId,
        public readonly string $userId,
    ) {}

    public function handle(ParseUsersFromFileAction $parser, PersistImportedUsersAction $persister): void
    {
        $company = Company::query()->findOrFail($this->companyId);
        $absPath = Storage::disk('local')->path($this->storagePath);

        try {
            $rows = $parser->execute($absPath, $this->fileExtension);
            $result = $persister->execute($rows, $company);

            Cache::put('import_done_' . $this->userId, $result->imported, 300);
        } finally {
            Storage::disk('local')->delete($this->storagePath);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ImportUsersJob falhou', [
            'company_id' => $this->companyId,
            'error' => $exception->getMessage(),
        ]);

        Storage::disk('local')->delete($this->storagePath);

        Cache::put('import_done_' . $this->userId, 'failed', 300);
    }
}

<?php

namespace TresPontosTech\User\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\Actions\PersistImportedUsersAction;

class ImportUsersJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function __construct(
        public readonly Collection $rows,
        public readonly string $companyId,
        public readonly string $userId,
    ) {}

    public static function completionCacheKey(string $userId): string
    {
        return 'import_done_' . $userId;
    }

    public function handle(PersistImportedUsersAction $persister): void
    {
        $company = Company::query()->findOrFail($this->companyId);
        $result = $persister->execute($this->rows, $company);

        Cache::put(self::completionCacheKey($this->userId), (string) $result->imported, now()->addHour());
    }

    public function failed(\Throwable $exception): void
    {
        Cache::put(self::completionCacheKey($this->userId), 'failed', now()->addHour());

        Log::error('ImportUsersJob falhou', [
            'company_id' => $this->companyId,
            'error' => $exception->getMessage(),
        ]);
    }
}

<?php

namespace TresPontosTech\User\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use TresPontosTech\User\Jobs\ImportUsersJob;

trait ChecksImportCompletion
{
    #[On('import-started')]
    public function startImportPolling(): void
    {
        $this->js(<<<'JS'
            window._importCheckInterval = setInterval(() => {
                $wire.checkImportCompletion()
            }, 3000);
        JS);
    }

    public function checkImportCompletion(): void
    {
        $result = Cache::pull(ImportUsersJob::completionCacheKey((string) auth()->id()));

        if ($result === null) {
            return;
        }

        $this->js('clearInterval(window._importCheckInterval)');

        if ($result === 'failed') {
            Notification::make()
                ->danger()
                ->title('Falha na importação')
                ->body('Ocorreu um erro inesperado. Tente novamente ou contate o suporte.')
                ->send();

            return;
        }

        $summary = $this->importSummary((string) $result);

        Notification::make()
            ->success()
            ->title('Importação concluída')
            ->body($this->importSummaryBody($summary))
            ->send();

        if ($summary['rows'] !== []) {
            $this->dispatch('import-result', rows: $summary['rows']);
        }
    }

    /**
     * A bare number is the count left in the cache by a job that finished before the FLM-110 deploy.
     *
     * @return array{imported: int, linked: int, reactivated: int, skipped: int, needs_support: int, rows: list<array{row: int, email: string, message: string}>}
     */
    private function importSummary(string $result): array
    {
        if (is_numeric($result)) {
            return ['imported' => (int) $result, 'linked' => 0, 'reactivated' => 0, 'skipped' => 0, 'needs_support' => 0, 'rows' => []];
        }

        /** @var array{imported: int, linked: int, reactivated: int, skipped: int, needs_support: int, rows: list<array{row: int, email: string, message: string}>} $summary */
        $summary = json_decode($result, associative: true, flags: JSON_THROW_ON_ERROR);

        return $summary;
    }

    /**
     * @param  array{imported: int, linked: int, reactivated: int, skipped: int, needs_support: int, rows: list<array{row: int, email: string, message: string}>}  $summary
     */
    private function importSummaryBody(array $summary): string
    {
        $imported = $summary['imported'];

        if ($summary['rows'] === []) {
            return $imported === 1
                ? 'O usuário foi importado com sucesso.'
                : sprintf('Todos os %d usuários foram importados com sucesso.', $imported);
        }

        $parts = collect([
            'imported' => $imported,
            'linked' => $summary['linked'],
            'reactivated' => $summary['reactivated'],
            'skipped' => $summary['skipped'],
            'needs_support' => $summary['needs_support'],
        ])
            ->filter()
            ->map(fn (int $count, string $key): string => trans_choice('user::actions.import_users.summary.' . $key, $count, ['count' => $count]))
            ->implode(', ');

        if ($imported + $summary['linked'] + $summary['reactivated'] === 0) {
            return __('user::actions.import_users.summary.none_joined', ['parts' => $parts]);
        }

        return $parts . '.';
    }
}

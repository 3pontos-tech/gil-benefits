<?php

namespace TresPontosTech\User\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

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
        $result = Cache::pull('import_done_' . auth()->id());

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

        Notification::make()
            ->success()
            ->title('Importação concluída')
            ->body($result . ' usuário(s) importado(s) com sucesso.')
            ->send();
    }
}

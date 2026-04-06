<?php

namespace TresPontosTech\User\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\Actions\ImportUsersFromFileAction;
use TresPontosTech\User\DTOs\ImportErrorDTO;
use TresPontosTech\User\DTOs\ImportUsersResultDTO;

class ImportUsersAction extends Action
{
    protected Company|Closure|null $company = null;

    public static function getDefaultName(): ?string
    {
        return 'import-users';
    }

    public function company(Company|Closure $company): static
    {
        $this->company = $company;

        return $this;
    }

    protected function resolveCompany(): Company
    {
        return $this->company instanceof Closure
            ? ($this->company)()
            : $this->company;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Importar Funcionários');
        $this->icon(Heroicon::ArrowUpTray);

        $this->schema([
            FileUpload::make('file')
                ->label('Planilha (CSV ou XLSX)')
                ->helperText('Colunas obrigatórias: name, email, tax_id, phone_number. Opcional: document_id.')
                ->hintAction(
                    Action::make('download_template')
                        ->label('Baixar Modelo')
                        ->url(route('users.import-template.download'))
                        ->openUrlInNewTab()
                )
                ->acceptedFileTypes([
                    'text/csv',
                    'text/plain',
                    'application/csv',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])
                ->storeFiles(false)
                ->required(),
        ]);

        $this->action(function (array $data): void {
            /** @var TemporaryUploadedFile $file */
            $file = $data['file'];

            /** @var ImportUsersResultDTO $result */
            $result = resolve(ImportUsersFromFileAction::class)->execute(
                filePath: $file->getRealPath(),
                fileExtension: $file->getClientOriginalExtension(),
                company: $this->resolveCompany(),
            );

            if ($result->imported > 0) {
                Notification::make()
                    ->success()
                    ->title('Importação concluída')
                    ->body($result->imported . ' usuário(s) importado(s) com sucesso.')
                    ->send();
            }

            if ($result->hasErrors()) {
                $errors = collect($result->errors)
                    ->map(fn (ImportErrorDTO $e): array => [
                        'row' => $e->row,
                        'email' => $e->email,
                        'message' => $e->message,
                    ])
                    ->values()
                    ->all();

                $this->getLivewire()->dispatch('import-errors', errors: $errors);
            }

            if ($result->isEmpty()) {
                Notification::make()
                    ->info()
                    ->title('Nenhum usuário importado')
                    ->body('A planilha está vazia ou todas as linhas foram ignoradas.')
                    ->send();
            }
        });
    }
}

<?php

namespace TresPontosTech\User\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\Actions\ParseUsersFromFileAction;
use TresPontosTech\User\Actions\ValidateUserImportAction;
use TresPontosTech\User\DTOs\ImportErrorDTO;
use TresPontosTech\User\Jobs\ImportUsersJob;

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
            $company = $this->resolveCompany();

            $rows = resolve(ParseUsersFromFileAction::class)->execute(
                $file->getRealPath(),
                $file->getClientOriginalExtension(),
            );

            if ($rows->isEmpty()) {
                Notification::make()
                    ->info()
                    ->title('Nenhum usuário importado')
                    ->body('A planilha está vazia ou todas as linhas foram ignoradas.')
                    ->send();

                return;
            }

            $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company);

            if ($errors !== []) {
                $this->getLivewire()->dispatch('import-errors', errors: collect($errors)
                    ->map(fn (ImportErrorDTO $e): array => [
                        'row' => $e->row,
                        'email' => $e->email,
                        'message' => $e->message,
                    ])
                    ->values()
                    ->all()
                );

                return;
            }

            dispatch(new ImportUsersJob(
                rows: $rows,
                companyId: $company->getKey(),
                userId: auth()->id()
            ))
                ->onQueue('users-import');

            $this->getLivewire()->dispatch('import-started');

            Notification::make()
                ->info()
                ->title('Importação em andamento')
                ->body('Você receberá uma notificação quando o processo for concluído.')
                ->persistent()
                ->send();
        });
    }
}

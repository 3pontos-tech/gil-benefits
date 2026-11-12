<?php

namespace TresPontosTech\User\Filament\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\User\Actions\BuildImportTemplateAction;
use TresPontosTech\User\Actions\ParseUsersFromFileAction;
use TresPontosTech\User\Actions\ValidateUserImportAction;
use TresPontosTech\User\DTOs\ImportErrorDTO;
use TresPontosTech\User\Exceptions\DuplicateImportHeadersException;
use TresPontosTech\User\Jobs\ImportUsersJob;
use TresPontosTech\User\Support\DepartmentLookup;

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

        $this->label(__('user::actions.import_users.label'));
        $this->icon(Heroicon::ArrowUpTray);

        $this->schema([
            FileUpload::make('file')
                ->label(__('user::actions.import_users.file.label'))
                ->helperText(new HtmlString(
                    __('user::actions.import_users.file.helper_text') .
                    '<br><br>' .
                    __('user::actions.import_users.file.helper_note')
                ))
                ->hintAction(
                    Action::make('download_template')
                        ->label(__('user::actions.import_users.file.hint_action'))
                        ->action(fn (): BinaryFileResponse => response()
                            ->download(
                                resolve(BuildImportTemplateAction::class)->execute($this->resolveCompany()),
                                BuildImportTemplateAction::FILENAME,
                            )
                            ->deleteFileAfterSend())
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
            $livewire = $this->getLivewire();

            try {
                $rows = resolve(ParseUsersFromFileAction::class)->execute(
                    $file->getRealPath(),
                    $file->getClientOriginalExtension(),
                );
            } catch (DuplicateImportHeadersException $duplicateImportHeadersException) {
                $this->showImportErrors([new ImportErrorDTO(row: 1, email: 'N/A', message: $duplicateImportHeadersException->getMessage())]);

                return;
            }

            if ($rows->isEmpty()) {
                Notification::make()
                    ->info()
                    ->title(__('user::actions.import_users.notifications.empty.title'))
                    ->body(__('user::actions.import_users.notifications.empty.body'))
                    ->send();

                return;
            }

            $departments = DepartmentLookup::for($company);
            $errors = resolve(ValidateUserImportAction::class)->execute($rows, $company, $departments);

            if ($errors !== []) {
                $this->showImportErrors($errors);

                return;
            }

            $userId = auth()->id();

            throw_if($userId === null, \RuntimeException::class, 'Cannot import users without an authenticated user.');

            dispatch(new ImportUsersJob(
                rows: $departments->withDepartmentIds($rows),
                companyId: (string) $company->getKey(),
                userId: (string) $userId
            ))
                ->onQueue('users-import');

            if ($livewire instanceof Component) {
                $livewire->dispatch('import-started');
            }

            Notification::make()
                ->info()
                ->title(__('user::actions.import_users.notifications.started.title'))
                ->body(__('user::actions.import_users.notifications.started.body'))
                ->persistent()
                ->send();
        });
    }

    /**
     * @param  list<ImportErrorDTO>  $errors
     */
    private function showImportErrors(array $errors): void
    {
        $livewire = $this->getLivewire();

        if (! $livewire instanceof Component) {
            return;
        }

        $livewire->dispatch('import-errors', errors: collect($errors)
            ->map(fn (ImportErrorDTO $error): array => [
                'row' => $error->row,
                'email' => $error->email,
                'message' => $error->message,
            ])
            ->values()
            ->all()
        );
    }
}

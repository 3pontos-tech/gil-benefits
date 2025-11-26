<?php

namespace App\Filament\Guest\Pages;

use App\Actions\RegisterPartnerCollaboratorAction;
use App\DTO\PartnerRegistrationDTO;
use App\Models\Users\Detail;
use App\Utils\CpfValidator;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use TresPontosTech\Company\Models\Company;

class PartnerRegistrationPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.guest.pages.partner-registration';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Cadastro de Colaborador Parceiro';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dados Pessoais')
                    ->description('Preencha os dados do colaborador')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Digite o nome completo')
                            ->live(onBlur: true)
                            ->rules(['required', 'string', 'max:255']),

                        TextInput::make('rg')
                            ->label('RG')
                            ->required()
                            ->maxLength(20)
                            ->placeholder('Digite o RG')
                            ->live(onBlur: true)
                            ->rules(['required', 'string', 'max:20']),

                        TextInput::make('cpf')
                            ->label('CPF')
                            ->required()
                            ->mask('999.999.999-99')
                            ->placeholder('000.000.000-00')
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'string',
                                function ($attribute, $value, $fail) {
                                    if (!CpfValidator::validate($value)) {
                                        $fail('CPF inválido. Verifique o formato');
                                    }
                                },
                            ])
                            ->rule(function () {
                                return function ($attribute, $value, $fail) {
                                    $cleanCpf = CpfValidator::clean($value);
                                    if (Detail::where('tax_id', $cleanCpf)->exists()) {
                                        $fail('Este CPF já está cadastrado no sistema');
                                    }
                                };
                            })
                            ->validationMessages([
                                'unique' => 'Este CPF já está cadastrado no sistema',
                            ]),

                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Digite o e-mail')
                            ->live(onBlur: true)
                            ->unique('users', 'email')
                            ->validationMessages([
                                'email' => 'Digite um e-mail válido',
                                'unique' => 'Este e-mail já está cadastrado no sistema',
                            ]),
                    ]),

                Section::make('Dados de Acesso')
                    ->description('Defina a senha de acesso')
                    ->schema([
                        TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->required()
                            ->placeholder('Digite a senha')
                            ->rules([Password::min(8)])
                            ->validationMessages([
                                'min' => 'A senha deve ter pelo menos 8 caracteres',
                            ]),

                        TextInput::make('password_confirmation')
                            ->label('Confirmar Senha')
                            ->password()
                            ->required()
                            ->placeholder('Confirme a senha')
                            ->same('password')
                            ->validationMessages([
                                'same' => 'As senhas não coincidem',
                            ]),
                    ]),

                Section::make('Dados da Empresa')
                    ->description('Informe o código do parceiro')
                    ->schema([
                        TextInput::make('partner_code')
                            ->label('Código do Parceiro')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('Digite o código do parceiro')
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'string',
                                'max:50',
                                function ($attribute, $value, $fail) {
                                    if (!$this->validatePartnerCode($value)) {
                                        $fail('Código de parceiro inválido ou não encontrado');
                                    }
                                },
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        try {
            $data = $this->form->getState();

            // Create DTO from form data
            $dto = PartnerRegistrationDTO::fromArray($data);

            // Execute registration action
            $action = new RegisterPartnerCollaboratorAction();
            $result = $action->execute($dto);

            if ($result->isSuccess()) {
                // Clear form data
                $this->form->fill();

                // Show success notification
                Notification::make()
                    ->title('Cadastro realizado com sucesso!')
                    ->body('O colaborador foi cadastrado com sucesso. Você pode fazer login na plataforma usando o e-mail e senha cadastrados.')
                    ->success()
                    ->persistent()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('login')
                            ->label('Fazer Login')
                            ->url('/app/login')
                            ->button(),
                    ])
                    ->send();

                // Redirect to login page after a short delay
                $this->redirect('/app/login');
            } else {
                // Show error notification
                Notification::make()
                    ->title('Erro no cadastro')
                    ->body($result->error)
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            // Show generic error notification
            Notification::make()
                ->title('Erro interno')
                ->body('Ocorreu um erro interno. Tente novamente mais tarde.')
                ->danger()
                ->send();
        }
    }

    /**
     * Validate partner code against companies table
     */
    public function validatePartnerCode(string $partnerCode): bool
    {
        if (empty($partnerCode)) {
            return false;
        }

        return Company::whereRaw('LOWER(partner_code) = LOWER(?)', [$partnerCode])->exists();
    }

    public function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('submit')
                ->label('Cadastrar Colaborador')
                ->submit('submit')
                ->keyBindings(['mod+s']),
        ];
    }
}
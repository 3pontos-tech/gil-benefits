<?php

namespace App\Filament\Guest\Pages;

use App\Actions\RegisterPartnerCollaboratorAction;
use App\DTO\PartnerRegistrationDTO;
use App\Models\Users\Detail;
use App\Rules\CpfRule;
use App\Rules\RgRule;
use App\Rules\UniqueCpfRule;
use App\Rules\ValidPartnerCodeRule;
use App\Utils\CpfValidator;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use TresPontosTech\Company\Models\Company;

class PartnerRegistrationPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.guest.pages.partner-registration';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Cadastro de Colaborador Parceiro';

    public ?array $data = [];

    public bool $isSubmitting = false;

    public bool $registrationSuccess = false;

    public ?string $successMessage = null;

    public ?string $redirectUrl = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
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
                            ->rules(['required', 'string', 'max:255'])
                            ->validationMessages([
                                'required' => 'O nome completo é obrigatório.',
                                'string' => 'O nome deve ser um texto válido.',
                                'max' => 'O nome não pode ter mais de 255 caracteres.',
                            ]),

                        TextInput::make('rg')
                            ->label('RG')
                            ->required()
                            ->maxLength(20)
                            ->placeholder('Digite o RG (ex: 12.345.678-9)')
                            ->live(onBlur: true)
                            ->rules(['required', new RgRule()])
                            ->validationMessages([
                                'required' => 'O RG é obrigatório.',
                                'max' => 'O RG não pode ter mais de 20 caracteres.',
                            ]),

                        TextInput::make('cpf')
                            ->label('CPF')
                            ->required()
                            ->mask('999.999.999-99')
                            ->placeholder('000.000.000-00')
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'string',
                                new CpfRule(),
                                new UniqueCpfRule(),
                            ])
                            ->validationMessages([
                                'required' => 'O CPF é obrigatório.',
                                'string' => 'O CPF deve ser um texto válido.',
                            ]),

                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Digite o e-mail (ex: usuario@exemplo.com)')
                            ->live(onBlur: true)
                            ->unique('users', 'email')
                            ->validationMessages([
                                'required' => 'O e-mail é obrigatório.',
                                'email' => 'Digite um e-mail válido (ex: usuario@exemplo.com).',
                                'unique' => 'Este e-mail já está cadastrado no sistema. Tente fazer login ou use outro e-mail.',
                                'max' => 'O e-mail não pode ter mais de 255 caracteres.',
                            ]),
                    ]),

                Section::make('Dados de Acesso')
                    ->description('Defina a senha de acesso')
                    ->schema([
                        TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->required()
                            ->placeholder('Digite uma senha segura (mínimo 8 caracteres)')
                            ->rules([
                                'required',
                                Password::min(8)
                                    ->letters()
                                    ->numbers()
                                    ->mixedCase()
                                    ->symbols()
                            ])
                            ->validationMessages([
                                'required' => 'A senha é obrigatória.',
                                'min' => 'A senha deve ter pelo menos 8 caracteres.',
                            ])
                            ->helperText('A senha deve conter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas, números e símbolos.'),

                        TextInput::make('password_confirmation')
                            ->label('Confirmar Senha')
                            ->password()
                            ->required()
                            ->placeholder('Digite a senha novamente')
                            ->same('password')
                            ->validationMessages([
                                'required' => 'A confirmação de senha é obrigatória.',
                                'same' => 'As senhas não coincidem. Digite a mesma senha nos dois campos.',
                            ]),
                    ]),

                Section::make('Dados da Empresa')
                    ->description('Informe o código do parceiro')
                    ->schema([
                        TextInput::make('partner_code')
                            ->label('Código do Parceiro')
                            ->required()
                            ->maxLength(50)
                            ->placeholder('Digite o código fornecido pela empresa parceira')
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'string',
                                'max:50',
                                new ValidPartnerCodeRule(),
                            ])
                            ->validationMessages([
                                'required' => 'O código do parceiro é obrigatório.',
                                'string' => 'O código deve ser um texto válido.',
                                'max' => 'O código não pode ter mais de 50 caracteres.',
                            ])
                            ->helperText('Este código foi fornecido pela empresa parceira. Entre em contato com o responsável se não tiver o código.'),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        try {
            // Set loading state
            $this->isSubmitting = true;

            // Validate form data first
            $data = $this->form->getState();

            // Sanitize input data for security
            $data = $this->sanitizeInputData($data);

            // Log registration attempt for security monitoring
            $this->logRegistrationAttempt($data, 'attempt');

            // Create DTO from form data
            $dto = PartnerRegistrationDTO::fromArray($data);

            // Execute registration action
            $action = new RegisterPartnerCollaboratorAction();
            $result = $action->execute($dto);

            if ($result->isSuccess()) {
                // Log successful registration
                $this->logRegistrationAttempt($data, 'success', [
                    'user_id' => $result->user?->id,
                    'company_id' => $result->company?->id,
                ]);

                // Set success state
                $this->registrationSuccess = true;
                $this->successMessage = sprintf(
                    'Parabéns, %s! Seu cadastro foi concluído com sucesso. Você foi associado à empresa %s e agora pode acessar a plataforma usando seu e-mail (%s) e senha.',
                    $result->user->name,
                    $result->company->name,
                    $result->user->email
                );
                $this->redirectUrl = '/app/login';

                // Clear form data only on success
                $this->form->fill();

                // Show success notification with detailed instructions
                Notification::make()
                    ->title('Cadastro realizado com sucesso!')
                    ->body($this->successMessage)
                    ->success()
                    ->persistent()
                    ->send();

                // Redirect to login page after showing success message
                $this->js('setTimeout(() => { window.location.href = "/app/login"; }, 3000);');
            } else {
                // Log registration failure
                $this->logRegistrationAttempt($data, 'failure', [
                    'error' => $result->error,
                ]);

                // Show detailed error notification but preserve form data
                Notification::make()
                    ->title('Erro no cadastro')
                    ->body($result->error ?: 'Não foi possível completar o cadastro. Verifique os dados informados e tente novamente.')
                    ->danger()
                    ->persistent()
                    ->send();

                // Form data is preserved automatically by not calling form->fill()
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors are handled automatically by Filament
            // Form state is preserved automatically
            $this->logRegistrationAttempt($this->data ?? [], 'validation_error', [
                'errors' => $e->errors(),
            ]);
            
            throw $e;
        } catch (\Exception $e) {
            // Log unexpected errors
            $this->logRegistrationAttempt($this->data ?? [], 'system_error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Show user-friendly error notification and preserve form data
            Notification::make()
                ->title('Erro do sistema')
                ->body('Ocorreu um erro interno no sistema. Por favor, tente novamente em alguns minutos. Se o problema persistir, entre em contato com o suporte.')
                ->danger()
                ->persistent()
                ->send();

            // Form data is preserved by not calling form->fill()
            // Explicitly preserve form state by not modifying $this->data
        } finally {
            // Reset loading state
            $this->isSubmitting = false;
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
            Action::make('submit')
                ->label($this->isSubmitting ? 'Processando...' : 'Cadastrar Colaborador')
                ->submit('submit')
                ->disabled($this->isSubmitting || $this->registrationSuccess)
                ->keyBindings(['mod+s'])
                ->icon($this->isSubmitting ? 'heroicon-o-arrow-path' : 'heroicon-o-user-plus')
                ->iconPosition($this->isSubmitting ? 'before' : 'after')
                ->extraAttributes([
                    'class' => $this->isSubmitting ? 'animate-pulse' : '',
                ]),
        ];
    }

    /**
     * Sanitize input data for security
     */
    protected function sanitizeInputData(array $data): array
    {
        return [
            'name' => isset($data['name']) ? trim(html_entity_decode(strip_tags($data['name']), ENT_QUOTES, 'UTF-8')) : '',
            'rg' => isset($data['rg']) ? preg_replace('/[^0-9\-\.]/', '', $data['rg']) : '',
            'cpf' => isset($data['cpf']) ? preg_replace('/[^0-9\-\.]/', '', $data['cpf']) : '',
            'email' => isset($data['email']) ? strtolower(trim(strip_tags($data['email']))) : '',
            'password' => $data['password'] ?? '', // Don't sanitize password as it may contain special chars
            'password_confirmation' => $data['password_confirmation'] ?? '',
            'partner_code' => isset($data['partner_code']) ? trim(strip_tags($data['partner_code'])) : '',
        ];
    }

    /**
     * Comprehensive logging for security monitoring
     */
    protected function logRegistrationAttempt(array $data, string $status, array $additionalData = []): void
    {
        $request = request();
        
        $logData = [
            'status' => $status,
            'email' => $data['email'] ?? 'unknown',
            'partner_code' => $data['partner_code'] ?? 'unknown',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'timestamp' => now()->toISOString(),
            'session_id' => $request->session()->getId(),
            'request_id' => Str::uuid()->toString(),
        ];

        // Add additional context data
        $logData = array_merge($logData, $additionalData);

        // Detect potential security threats
        $securityFlags = $this->detectSecurityThreats($request, $data);
        if (!empty($securityFlags)) {
            $logData['security_flags'] = $securityFlags;
        }

        // Log with appropriate level based on status
        match ($status) {
            'success' => Log::info('Partner registration successful', $logData),
            'failure', 'validation_error' => Log::warning("Partner registration {$status}", $logData),
            'system_error' => Log::error('Partner registration system error', $logData),
            default => Log::info('Partner registration attempt', $logData),
        };

        // Additional security monitoring for suspicious activity
        if (!empty($securityFlags)) {
            Log::channel('security')->warning('Suspicious partner registration activity detected', $logData);
        }
    }

    /**
     * Detect potential security threats in registration attempts
     */
    protected function detectSecurityThreats($request, array $data): array
    {
        $flags = [];

        // Check for suspicious user agents
        $userAgent = $request->userAgent();
        if (empty($userAgent) || preg_match('/bot|crawler|spider|scraper/i', $userAgent)) {
            $flags[] = 'suspicious_user_agent';
        }

        // Check for suspicious email patterns
        $email = $data['email'] ?? '';
        if (preg_match('/[+].*[+]|\.{2,}|[0-9]{10,}/', $email)) {
            $flags[] = 'suspicious_email_pattern';
        }

        // Check for suspicious name patterns (too short, all caps, numbers only)
        $name = $data['name'] ?? '';
        if (strlen($name) < 3 || ctype_upper($name) || ctype_digit(str_replace(' ', '', $name))) {
            $flags[] = 'suspicious_name_pattern';
        }

        // Check for missing referer (direct access)
        if (empty($request->header('referer'))) {
            $flags[] = 'missing_referer';
        }

        // Check for rapid successive attempts (would need Redis/cache to implement properly)
        // This is a placeholder for future implementation
        
        return $flags;
    }

    /**
     * Redirect to login page manually
     */
    public function redirectToLogin(): void
    {
        $this->redirect('/app/login');
    }
}
<?php

declare(strict_types=1);

namespace TresPontosTech\App\Filament\Pages;

use App\Models\Users\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use TresPontosTech\User\Actions\SaveAnamneseAction;
use TresPontosTech\User\Enums\LifeMoment;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

/**
 * @property-read Schema $form
 */
class ProfilePage extends Page
{
    protected static ?string $slug = 'profile';

    protected static bool $shouldRegisterNavigation = false;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    protected string $view = 'filament.app.pages.profile';

    public function getTitle(): string
    {
        return __('all.my_profile');
    }

    public function getHeading(): string
    {
        return '';
    }

    public function mount(): void
    {
        $user = $this->getUser();
        $anamnese = $user->anamnese;

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->detail?->phone_number,
            'life_moment' => $anamnese?->getRawOriginal('life_moment'),
            'main_motivation' => $anamnese?->main_motivation,
            'money_relationship' => $anamnese?->money_relationship,
            'plans_monthly_expenses' => $anamnese?->plans_monthly_expenses,
            'tried_financial_strategies' => $anamnese?->tried_financial_strategies,
        ]);
    }

    protected function getUser(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model($this->getUser())
            ->statePath('data')
            ->components([
                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('panel-app::profile.sections.account'))
                            ->icon('heroicon-o-user')
                            ->schema([
                                Grid::make(['default' => 1, 'md' => 3])
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('avatar')
                                            ->label(__('panel-app::profile.fields.avatar'))
                                            ->collection('user_avatar')
                                            ->avatar()
                                            ->image()
                                            ->imageEditor()
                                            ->circleCropper()
                                            ->maxFiles(1)
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->alignCenter()
                                            ->extraFieldWrapperAttributes(['class' => 'profile-avatar-field'])
                                            ->columnSpan(['default' => 1, 'md' => 1]),
                                        Grid::make(1)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label(__('panel-app::profile.fields.name'))
                                                    ->required()
                                                    ->maxLength(255),
                                                TextInput::make('email')
                                                    ->label(__('panel-app::profile.fields.email'))
                                                    ->email()
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->unique(ignoreRecord: true)
                                                    ->live(debounce: 500),
                                                PhoneInput::make('phone_number')
                                                    ->label(__('panel-app::profile.fields.phone'))
                                                    ->defaultCountry('BR')
                                                    ->initialCountry('BR')
                                                    ->disableLookup()
                                                    ->strictMode(),
                                            ])
                                            ->columnSpan(['default' => 1, 'md' => 2]),
                                    ]),
                            ]),

                        Tab::make(__('panel-app::profile.sections.financial'))
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Select::make('life_moment')
                                    ->label(__('panel-app::anamnese.fields.life_moment'))
                                    ->options(fn (): Collection => collect(LifeMoment::cases())
                                        ->mapWithKeys(fn (LifeMoment $case): array => [
                                            $case->value => '<div style="display:flex;flex-direction:column;white-space:normal;line-height:1.4">'
                                                . '<span style="font-weight:500">' . e($case->getLabel()) . '</span>'
                                                . '<span style="font-size:0.75rem;color:#9ca3af">' . e($case->getDescription()) . '</span>'
                                                . '</div>',
                                        ])
                                    )
                                    ->native(false)
                                    ->allowHtml()
                                    ->required(),
                                Textarea::make('main_motivation')
                                    ->label(__('panel-app::anamnese.fields.main_motivation'))
                                    ->rows(4)
                                    ->required(),
                                Textarea::make('money_relationship')
                                    ->label(__('panel-app::anamnese.fields.money_relationship'))
                                    ->rows(4)
                                    ->required(),
                                Textarea::make('plans_monthly_expenses')
                                    ->label(__('panel-app::anamnese.fields.plans_monthly_expenses'))
                                    ->rows(3)
                                    ->required(),
                                Textarea::make('tried_financial_strategies')
                                    ->label(__('panel-app::anamnese.fields.tried_financial_strategies'))
                                    ->hint(__('panel-app::anamnese.fields.tried_financial_strategies_hint'))
                                    ->rows(3)
                                    ->required(),
                            ]),

                        Tab::make(__('panel-app::profile.sections.security'))
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                TextInput::make('currentPassword')
                                    ->label(__('panel-app::profile.fields.current_password'))
                                    ->password()
                                    ->revealable(filament()->arePasswordsRevealable())
                                    ->autocomplete('current-password')
                                    ->currentPassword(guard: Filament::getAuthGuard())
                                    ->required(fn (Get $get): bool => filled($get('password')))
                                    ->dehydrated(false),
                                TextInput::make('password')
                                    ->label(__('panel-app::profile.fields.new_password'))
                                    ->password()
                                    ->revealable(filament()->arePasswordsRevealable())
                                    ->rule(Password::default())
                                    ->autocomplete('new-password')
                                    ->dehydrated(fn ($state): bool => filled($state))
                                    ->dehydrateStateUsing(fn ($state): string => Hash::make(is_string($state) ? $state : ''))
                                    ->live(debounce: 500)
                                    ->same('passwordConfirmation')
                                    ->helperText(__('panel-app::profile.sections.security_hint')),
                                TextInput::make('passwordConfirmation')
                                    ->label(__('panel-app::profile.fields.password_confirmation'))
                                    ->password()
                                    ->revealable(filament()->arePasswordsRevealable())
                                    ->autocomplete('new-password')
                                    ->required(fn (Get $get): bool => filled($get('password')))
                                    ->visible(fn (Get $get): bool => filled($get('password')))
                                    ->dehydrated(false),
                            ]),

                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = $this->getUser();

        $toString = static fn (mixed $value): string => is_scalar($value) ? (string) $value : '';

        DB::transaction(function () use ($user, $data, $toString): void {
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];

            if (filled($data['password'] ?? null)) {
                $userData['password'] = $data['password'];
            }

            $user->update($userData);

            $user->detail()->updateOrCreate(
                ['user_id' => $user->getKey()],
                ['phone_number' => $data['phone_number'] ?? null],
            );

            resolve(SaveAnamneseAction::class)->handle($user, [
                'life_moment' => $toString($data['life_moment'] ?? null),
                'main_motivation' => $toString($data['main_motivation'] ?? null),
                'money_relationship' => $toString($data['money_relationship'] ?? null),
                'plans_monthly_expenses' => $toString($data['plans_monthly_expenses'] ?? null),
                'tried_financial_strategies' => $toString($data['tried_financial_strategies'] ?? null),
            ]);
        });

        $this->form->saveRelationships();

        $this->data['password'] = null;
        $this->data['passwordConfirmation'] = null;
        $this->data['currentPassword'] = null;

        Notification::make()
            ->title(__('panel-app::profile.notifications.saved'))
            ->success()
            ->send();
    }
}

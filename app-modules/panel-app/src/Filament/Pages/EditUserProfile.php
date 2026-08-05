<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Pages;

use App\Filament\Shared\Pages\EditUserProfile as BaseEditUserProfile;
use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\Actions\BuildAccountSummaryAction;
use TresPontosTech\PanelApp\Filament\Resources\SupportTickets\SupportTicketResource;
use TresPontosTech\User\Actions\SaveAnamneseAction;
use TresPontosTech\User\Enums\LifeMoment;

class EditUserProfile extends BaseEditUserProfile
{
    protected Width|string|null $maxWidth = Width::Full;

    private const int AVATAR_MAX_SIZE_KB = 5120;

    /** @var list<string> */
    private const array ANAMNESE_FIELDS = [
        'life_moment',
        'main_motivation',
        'money_relationship',
        'plans_monthly_expenses',
        'tried_financial_strategies',
    ];

    public function getHeading(): string|Htmlable
    {
        /** @var User $user */
        $user = $this->getUser();

        return new HtmlString((string) __('panel-app::profile.heading', [
            'name' => '<span class="text-danger-500">' . e($user->name) . '</span>',
        ]));
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('panel-app::profile.subheading');
    }

    public function defaultForm(Schema $schema): Schema
    {
        return parent::defaultForm($schema)->inlineLabel(false);
    }

    public function content(Schema $schema): Schema
    {
        /** @var User $user */
        $user = $this->getUser();
        $supportUrl = $this->resolveSupportUrl($user);

        return $schema->components([
            Grid::make()
                ->columns(['default' => 1, 'xl' => 3])
                ->schema([
                    Group::make([$this->getFormContentComponent()])
                        ->columnSpan(['default' => 1, 'xl' => 2]),

                    Group::make([
                        View::make('filament.app.profile.account-summary')
                            ->viewData([
                                'rows' => resolve(BuildAccountSummaryAction::class)->handle($user),
                            ]),
                        View::make('filament.app.profile.support')
                            ->viewData(['supportUrl' => $supportUrl])
                            ->visible($supportUrl !== null),
                    ])->columnSpan(1),
                ]),

            ...Arr::wrap($this->getMultiFactorAuthenticationContentComponent()),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getSectionHeadingComponent(
                (string) __('panel-app::profile.avatar.heading'),
                (string) __('panel-app::profile.avatar.description'),
            ),
            $this->getAvatarFormComponent(),

            Tabs::make()
                ->columnSpanFull()
                ->extraAttributes(['class' => 'fi-ap-profile-tabs'])
                ->tabs([
                    Tab::make(__('panel-app::profile.tabs.account'))
                        ->schema($this->getPersonalTabSchema()),
                    Tab::make(__('panel-app::profile.tabs.financial'))
                        ->schema($this->getFinancialTabSchema()),
                ]),
        ]);
    }

    /**
     * @return array<Component>
     */
    private function getPersonalTabSchema(): array
    {
        return [
            Section::make(__('panel-app::profile.personal.heading'))
                ->description(__('panel-app::profile.personal.description'))
                ->schema([
                    $this->withPrefixIcon($this->getNameFormComponent(), 'heroicon-o-user'),
                    $this->withPrefixIcon($this->getEmailFormComponent(), 'heroicon-o-envelope'),
                    $this->getPhoneFormComponent(),
                    ...$this->getExtraDetailFormComponents(),
                ]),

            Section::make(__('panel-app::profile.security.heading'))
                ->description(__('panel-app::profile.security.description'))
                ->schema([
                    $this->withPrefixIcon($this->getPasswordFormComponent(), 'heroicon-o-lock-closed'),
                    $this->withPrefixIcon($this->getPasswordConfirmationFormComponent(), 'heroicon-o-lock-closed'),
                    $this->withPrefixIcon($this->getCurrentPasswordFormComponent(), 'heroicon-o-lock-closed'),
                ]),
        ];
    }

    /**
     * @return array<Component>
     */
    private function getFinancialTabSchema(): array
    {
        return [
            Section::make(__('panel-app::profile.financial.heading'))
                ->description(__('panel-app::profile.financial.description'))
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
                        ->rows(3)
                        ->required(),
                ]),
        ];
    }

    protected function getAvatarFormComponent(): Component
    {
        $component = parent::getAvatarFormComponent();

        return $component instanceof SpatieMediaLibraryFileUpload
            ? $component
                ->hiddenLabel()
                ->helperText(__('panel-app::profile.avatar.helper'))
                ->maxSize(self::AVATAR_MAX_SIZE_KB)
            : $component;
    }

    private function resolveSupportUrl(User $user): ?string
    {
        $tenant = Filament::getTenant() ?? $user->companies()->first();

        return $tenant instanceof Company
            ? SupportTicketResource::getUrl('create', tenant: $tenant)
            : null;
    }

    private function getSectionHeadingComponent(string $heading, string $description): Component
    {
        return View::make('filament.app.profile.section-heading')
            ->viewData([
                'heading' => $heading,
                'description' => $description,
            ]);
    }

    private function withPrefixIcon(Component $component, string $icon): Component
    {
        return $component instanceof TextInput
            ? $component->prefixIcon($icon)
            : $component;
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label(__('panel-app::profile.actions.save'))
            ->extraAttributes(['class' => 'fi-ap-profile-save']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        /** @var User $user */
        $user = $this->getUser();
        $anamnese = $user->anamnese;

        $data['life_moment'] = $anamnese?->getRawOriginal('life_moment');
        $data['main_motivation'] = $anamnese?->main_motivation;
        $data['money_relationship'] = $anamnese?->money_relationship;
        $data['plans_monthly_expenses'] = $anamnese?->plans_monthly_expenses;
        $data['tried_financial_strategies'] = $anamnese?->tried_financial_strategies;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $anamneseData = [
                'life_moment' => (string) $data['life_moment'],
                'main_motivation' => (string) $data['main_motivation'],
                'money_relationship' => (string) $data['money_relationship'],
                'plans_monthly_expenses' => (string) $data['plans_monthly_expenses'],
                'tried_financial_strategies' => (string) $data['tried_financial_strategies'],
            ];
            $profileData = Arr::except($data, self::ANAMNESE_FIELDS);

            /** @var User $updatedRecord */
            $updatedRecord = parent::handleRecordUpdate($record, $profileData);

            resolve(SaveAnamneseAction::class)->handle($updatedRecord, $anamneseData);

            return $updatedRecord;
        });
    }
}

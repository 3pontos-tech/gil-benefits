<?php

namespace TresPontosTech\App\Filament\Pages;

use App\Filament\Shared\Fields\LifeMomentSelector;
use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use TresPontosTech\User\Actions\SaveAnamneseAction;

class AnamneseWizardPage extends Page
{
    protected static ?string $slug = 'anamnese';

    protected static string $layout = 'filament-panels::components.layout.simple';

    protected Width|string|null $maxContentWidth = '4xl';

    protected string $view = 'anamnese-wizard';

    protected static bool $shouldRegisterNavigation = false;

    public static function isTenantSubscriptionRequired(Panel $panel): bool
    {
        return false;
    }

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->schema([
            Wizard::make([
                Step::make(__('panel-app::anamnese.steps.financial_profile'))
                    ->icon(Heroicon::Banknotes)
                    ->schema([
                        LifeMomentSelector::make('life_moment')
                            ->label(__('panel-app::anamnese.fields.life_moment'))
                            ->required(),
                    ]),

                Step::make(__('panel-app::anamnese.steps.motivation'))
                    ->icon(Heroicon::ChatBubbleBottomCenterText)
                    ->schema([
                        Textarea::make('main_motivation')
                            ->label(__('panel-app::anamnese.fields.main_motivation'))
                            ->rows(4)
                            ->required(),

                        Textarea::make('money_relationship')
                            ->label(__('panel-app::anamnese.fields.money_relationship'))
                            ->rows(4)
                            ->required(),
                    ]),

                Step::make(__('panel-app::anamnese.steps.habits'))
                    ->icon(Heroicon::ClipboardDocumentList)
                    ->schema([
                        Textarea::make('plans_monthly_expenses')
                            ->label(__('panel-app::anamnese.fields.plans_monthly_expenses'))
                            ->rows(3)
                            ->required(),

                        Textarea::make('tried_financial_strategies')
                            ->label(new HtmlString(
                                e(__('panel-app::anamnese.fields.tried_financial_strategies')) .
                                '<sup class="fi-fo-field-label-required-mark">*</sup>' .
                                '<p class="text-xs text-gray-500 dark:text-gray-400 font-normal mt-0.5">' .
                                e(__('panel-app::anamnese.fields.tried_financial_strategies_hint')) .
                                '</p>'
                            ))
                            ->validationAttribute(__('panel-app::anamnese.fields.tried_financial_strategies'))
                            ->required()
                            ->markAsRequired(false)
                            ->rows(3)
                            ->live(),

                    ]),
                Step::make(__('panel-app::anamnese.steps.review'))
                    ->icon(Heroicon::CheckCircle)
                    ->schema([
                        ViewField::make('summary')
                            ->view('forms.fields.anamnese-summary')
                            ->dehydrated(false),
                    ]),
            ])
                ->columnSpanFull()
                ->submitAction(
                    Action::make('submit')
                        ->label(__('panel-app::anamnese.actions.submit'))
                        ->icon('heroicon-m-arrow-right')
                        ->iconPosition('after')
                        ->action('submit')
                ),
        ]);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        /** @var User $user */
        $user = auth()->user();

        resolve(SaveAnamneseAction::class)->handle($user, $data);

        Notification::make()
            ->title(__('panel-app::anamnese.notifications.saved'))
            ->success()
            ->send();

        $this->redirect(UserDashboard::getUrl());
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\App\Filament\Pages;

use App\Filament\Shared\Pages\EditUserProfile as BaseEditUserProfile;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use TresPontosTech\User\Actions\SaveAnamneseAction;
use TresPontosTech\User\Enums\LifeMoment;

class EditUserProfile extends BaseEditUserProfile
{
    protected Width|string|null $maxWidth = Width::FourExtraLarge;

    /** @var list<string> */
    private const array ANAMNESE_FIELDS = [
        'life_moment',
        'main_motivation',
        'money_relationship',
        'plans_monthly_expenses',
        'tried_financial_strategies',
    ];

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    Tab::make(__('panel-app::profile.tabs.account'))
                        ->icon('heroicon-o-user')
                        ->schema([
                            $this->getNameFormComponent(),
                            $this->getEmailFormComponent(),
                            $this->getPhoneFormComponent(),
                            ...$this->getExtraDetailFormComponents(),
                            $this->getPasswordFormComponent(),
                            $this->getPasswordConfirmationFormComponent(),
                            $this->getCurrentPasswordFormComponent(),
                        ]),
                    Tab::make(__('panel-app::profile.tabs.financial'))
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
                                ->rows(3)
                                ->required(),
                        ]),
                ]),
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        $anamnese = $this->getUser()->anamnese;

        $data['life_moment'] = $anamnese?->getRawOriginal('life_moment');
        $data['main_motivation'] = $anamnese?->main_motivation;
        $data['money_relationship'] = $anamnese?->money_relationship;
        $data['plans_monthly_expenses'] = $anamnese?->plans_monthly_expenses;
        $data['tried_financial_strategies'] = $anamnese?->tried_financial_strategies;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            $anamneseData = Arr::only($data, self::ANAMNESE_FIELDS);
            $profileData = Arr::except($data, self::ANAMNESE_FIELDS);

            $updatedRecord = parent::handleRecordUpdate($record, $profileData);

            resolve(SaveAnamneseAction::class)->handle($updatedRecord, $anamneseData);

            return $updatedRecord;
        });
    }
}

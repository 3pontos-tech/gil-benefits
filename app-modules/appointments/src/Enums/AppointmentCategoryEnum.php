<?php

namespace TresPontosTech\Appointments\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum AppointmentCategoryEnum: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case PersonalFinance = 'personal_finance';

    case InvestmentAdvisory = 'investment_advisory';

    case RetirementAndEstatePlanning = 'retirement_and_estate_planning';

    case BusinessFinancialManagement = 'business_financial_management';

    case TaxPlanning = 'tax_planning';

    case FundraisingAndCredit = 'fundraising_and_credit';

    case MergersAndAcquisitions = 'mergers_and_acquisitions';

    case RiskAndCompliance = 'risk_and_compliance';

    public function getDescription(): string|Htmlable|null
    {
        return __('appointments::categories.' . $this->value . '.description');
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::PersonalFinance => Heroicon::CurrencyDollar,
            self::InvestmentAdvisory => Heroicon::ChartBar,
            self::RetirementAndEstatePlanning => Heroicon::Home,
            self::BusinessFinancialManagement => Heroicon::Briefcase,
            self::TaxPlanning => Heroicon::Calculator,
            self::FundraisingAndCredit => Heroicon::HomeModern,
            self::MergersAndAcquisitions => Heroicon::BuildingOffice2,
            self::RiskAndCompliance => Heroicon::ShieldCheck,
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::PersonalFinance => Color::Green,
            self::InvestmentAdvisory => Color::Blue,
            self::RetirementAndEstatePlanning => Color::Purple,
            self::BusinessFinancialManagement => Color::Orange,
            self::TaxPlanning => Color::Pink,
            self::FundraisingAndCredit => Color::Teal,
            self::MergersAndAcquisitions => Color::Indigo,
            self::RiskAndCompliance => Color::Red,
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return __('appointments::categories.' . $this->value . '.label');
    }
}

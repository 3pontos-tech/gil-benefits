<?php

namespace TresPontosTech\Appointments\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum AppointmentCategoryEnum: string implements HasDescription, HasIcon, HasLabel
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
        return match ($this) {
            self::PersonalFinance => 'Assistance with budgeting, saving, and managing personal finances.',
            self::InvestmentAdvisory => 'Guidance on investment strategies and portfolio management.',
            self::RetirementAndEstatePlanning => 'Planning for retirement and managing estate affairs.',
            self::BusinessFinancialManagement => 'Support for business financial operations and strategy.',
            self::TaxPlanning => 'Advice on tax strategies and compliance.',
            self::FundraisingAndCredit => 'Help with fundraising efforts and credit management.',
            self::MergersAndAcquisitions => 'Consultation on mergers, acquisitions, and business growth.',
            self::RiskAndCompliance => 'Ensuring adherence to regulations and managing financial risks.',
        };
    }

    public function getIcon(): BackedEnum
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

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::PersonalFinance => 'Personal Finance',
            self::InvestmentAdvisory => 'Investment Advisory',
            self::RetirementAndEstatePlanning => 'Retirement and Estate Planning',
            self::BusinessFinancialManagement => 'Business Financial Management',
            self::TaxPlanning => 'Tax Planning',
            self::FundraisingAndCredit => 'Fundraising and Credit',
            self::MergersAndAcquisitions => 'Mergers and Acquisitions',
            self::RiskAndCompliance => 'Risk and Compliance',
        };
    }
}

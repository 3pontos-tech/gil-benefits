<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Widgets;

use App\Models\Users\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Billing\Core\Actions\ResolveQuotaAllowance;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Billing\Core\Support\QuotaCycle;
use TresPontosTech\PanelApp\DTOs\PlanSummary;
use TresPontosTech\PanelApp\Enums\PlanStatus;
use TresPontosTech\PanelApp\Filament\Concerns\SchedulesAppointments;
use TresPontosTech\PanelApp\Filament\Pages\UserCreditsPage;
use TresPontosTech\PanelApp\Support\BookingBlockReasons;

class PlanCreditsWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use SchedulesAppointments;

    protected string $view = 'filament.app.widgets.plan-credits';

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 5];

    private ?PlanSummary $resolvedPlan = null;

    private bool $planResolved = false;

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $plan = $this->plan();

        // O cartão mostra só quantos créditos o cliente tem disponíveis, sem
        // distinguir origem, por isso basta a contagem. O filtro de tenant
        // acompanha a página "Meus Créditos" — sem ele, quem tem crédito em
        // mais de uma empresa veria aqui um total diferente do da listagem.
        $availableCredits = UserCredit::query()
            ->where('holder_id', $user->getKey())
            ->where('company_id', filament()->getTenant()?->getKey())
            ->where('status', UserCreditStatusEnum::Available)
            ->count();

        $monthlyLeft = $user->monthly_appointments_left;
        $hasCredit = $availableCredits > 0;
        $hasOngoingAppointment = $user->hasOngoingAppointment();
        $canCreateAppointment = ($monthlyLeft > 0 || $hasCredit) && ! $hasOngoingAppointment;

        return [
            'plan' => $plan,
            'monthlyLeft' => $monthlyLeft,
            'monthlyLimit' => $plan->monthlyLimit ?? 0,
            'renewsAt' => $this->renewalDate($user),
            'creditsTotal' => $availableCredits,
            'canCreateAppointment' => $canCreateAppointment,
            'blockReasons' => BookingBlockReasons::from($hasOngoingAppointment, $monthlyLeft > 0 || $hasCredit),
            'holderName' => str($user->name)->trim()->upper()->value(),
            'consultantName' => $this->currentConsultantName($user),
            'creditsUrl' => UserCreditsPage::getUrl(),
        ];
    }

    /**
     * Dia em que a cota do plano volta, derivado da mesma âncora que calcula o saldo.
     *
     * É a resposta que o cliente não tinha: enquanto a janela era rolante, a data mudava
     * a cada agendamento e não havia o que exibir. Devolve `null` para quem não tem plano
     * nem assinatura, e nesse caso a linha não é renderizada.
     */
    private function renewalDate(User $user): ?CarbonImmutable
    {
        $allowance = resolve(ResolveQuotaAllowance::class)->for($user);

        if ($allowance->isEmpty()) {
            return null;
        }

        return QuotaCycle::forAnchor($allowance->anchor)->end;
    }

    /**
     * Consultor da consultoria mais recente — é o vínculo que o usuário
     * reconhece como "o meu consultor"; não há atribuição fixa no modelo.
     */
    private function currentConsultantName(User $user): ?string
    {
        return $user->appointments()
            ->with('consultant')
            // Consulta cancelada não representa vínculo: sem o filtro, o último
            // cancelamento apareceria como "meu consultor" com bolinha verde.
            ->whereIn('status', [
                AppointmentStatus::Pending,
                AppointmentStatus::Active,
                AppointmentStatus::Completed,
            ])
            ->latest('appointment_at')
            ->first()
            ?->consultant
            ?->name;
    }

    public function viewPlanAction(): Action
    {
        $plan = $this->plan();

        return Action::make('viewPlan')
            ->link()
            // O layout pede um "Acessar meu plano →" no topo do card; em vez de
            // um link novo, reaproveita o modal que já descreve o plano.
            ->label(__('panel-app::widgets.plan_credits.access_plan'))
            ->icon(Heroicon::ArrowRight)
            ->iconPosition(IconPosition::After)
            ->color('primary')
            ->size(Size::ExtraSmall)
            ->extraAttributes(['class' => 'whitespace-nowrap'])
            ->visible($plan instanceof PlanSummary)
            ->modalHeading($plan?->name)
            ->modalWidth(Width::Medium)
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalContent(fn (): View => view('filament.app.widgets.partials.plan-details', [
                'plan' => $this->plan(),
            ]));
    }

    #[On('appointment-cancelled')]
    #[On('appointment-booked')]
    #[On('appointment-rescheduled')]
    public function refresh(): void {}

    private function plan(): ?PlanSummary
    {
        if (! $this->planResolved) {
            /** @var User $user */
            $user = auth()->user();
            $this->resolvedPlan = $this->resolvePlan($user);
            $this->planResolved = true;
        }

        return $this->resolvedPlan;
    }

    private function resolvePlan(User $user): ?PlanSummary
    {
        $contractualPlan = resolve(ResolveQuotaAllowance::class)->contractualPlanFor($user);
        $contractualPlan?->loadMissing('plan');

        if ($contractualPlan !== null && $contractualPlan->plan !== null) {
            $limit = (int) $contractualPlan->monthly_appointments_per_employee;

            return new PlanSummary(
                name: $contractualPlan->plan->name,
                status: PlanStatus::Active,
                description: $contractualPlan->plan->description,
                monthlyLimit: $limit,
                features: [
                    trans_choice('panel-app::widgets.plan_details.monthly_appointments', $limit, ['count' => $limit]),
                ],
            );
        }

        /** @var Subscription|null $subscription */
        $subscription = $user->activeSubscription()->with('price.plan')->first();
        $price = $subscription?->price;
        $plan = $price?->plan;

        if ($subscription === null || $price === null || $plan === null) {
            return null;
        }

        $status = $subscription->ends_at !== null
            ? PlanStatus::Expired
            : ($subscription->stripe_status === 'active' ? PlanStatus::Active : PlanStatus::Inactive);

        $limit = (int) $price->monthly_appointments;

        $features = [
            trans_choice('panel-app::widgets.plan_details.monthly_appointments', $limit, ['count' => $limit]),
        ];

        if ($price->whatsapp_enabled) {
            $features[] = __('panel-app::widgets.plan_details.whatsapp');
        }

        if ($price->materials_enabled) {
            $features[] = __('panel-app::widgets.plan_details.materials');
        }

        return new PlanSummary(
            name: $plan->name,
            status: $status,
            description: $plan->description,
            monthlyLimit: $limit,
            features: $features,
        );
    }
}

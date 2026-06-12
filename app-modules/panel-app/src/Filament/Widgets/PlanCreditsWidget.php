<?php

declare(strict_types=1);

namespace TresPontosTech\App\Filament\Widgets;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use TresPontosTech\App\DTOs\PlanSummary;
use TresPontosTech\App\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;
use TresPontosTech\Billing\Core\Models\UserCredit;

class PlanCreditsWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.app.widgets.plan-credits';

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 3];

    private ?PlanSummary $resolvedPlan = null;

    private bool $planResolved = false;

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $plan = $this->plan();

        $availableCredits = UserCredit::query()
            ->where('holder_id', $user->getKey())
            ->where('status', UserCreditStatusEnum::Available)
            ->count();

        $monthlyLeft = $user->monthly_appointments_left;
        $hasCredit = $availableCredits > 0;
        $hasOngoingAppointment = $user->hasOngoingAppointment();
        $canCreateAppointment = ($monthlyLeft > 0 || $hasCredit) && ! $hasOngoingAppointment;

        return [
            'plan' => $plan,
            'monthlyLeft' => $monthlyLeft,
            'monthlyLimit' => $plan?->monthlyLimit ?? 0,
            'availableCredits' => $availableCredits,
            'canCreateAppointment' => $canCreateAppointment,
            'blockReasons' => $this->blockReasons($hasOngoingAppointment, $monthlyLeft, $hasCredit),
        ];
    }

    public function viewPlanAction(): Action
    {
        $plan = $this->plan();

        return Action::make('viewPlan')
            ->link()
            ->label(__('panel-app::widgets.plan_details.view_plan'))
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
    public function refresh(): void {}

    public function redirectToAppointmentCreation(): void
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->canCreateAppointment()) {
            Notification::make()
                ->title(__('panel-app::resources.appointments.pages.create.cannot_book_now'))
                ->body(__('panel-app::resources.appointments.pages.create.no_appointments_available'))
                ->danger()
                ->send();

            return;
        }

        redirect()->intended(AppointmentResource::getUrl('create'));
    }

    /**
     * @return list<string>
     */
    private function blockReasons(bool $hasOngoingAppointment, int $monthlyLeft, bool $hasCredit): array
    {
        $reasons = [];

        if ($hasOngoingAppointment) {
            $reasons[] = __('panel-app::widgets.plans_overview.ongoing_appointment');
        }

        if ($monthlyLeft <= 0 && ! $hasCredit) {
            $reasons[] = __('panel-app::widgets.plans_overview.no_appointments_available');
        }

        return $reasons;
    }

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
        $contractualPlan = CompanyPlan::query()
            ->whereIn('company_id', $user->companies()->select('companies.id'))
            ->where('status', CompanyPlanStatusEnum::Active)
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->with('plan')
            ->first();

        if ($contractualPlan !== null && $contractualPlan->plan !== null) {
            $limit = (int) $contractualPlan->monthly_appointments_per_employee;

            return new PlanSummary(
                name: $contractualPlan->plan->name,
                status: 'active',
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
            ? 'expired'
            : ($subscription->stripe_status === 'active' ? 'active' : 'inactive');

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

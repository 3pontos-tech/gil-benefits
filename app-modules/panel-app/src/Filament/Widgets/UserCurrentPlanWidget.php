<?php

namespace TresPontosTech\App\Filament\Widgets;

use App\Models\Users\User;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\App\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\Billing\Core\Enums\CompanyPlanStatusEnum;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

class UserCurrentPlanWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.plans-overview';

    protected int|string|array $columnSpan = 4;

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $contractualPlan = CompanyPlan::query()
            ->whereIn('company_id', $user->companies()->select('companies.id'))
            ->where('status', CompanyPlanStatusEnum::Active)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->with('plan')
            ->first();

        if (filled($contractualPlan)) {
            return [
                'planName' => $contractualPlan->plan->name,
                'description' => $contractualPlan->plan->description,
                'status' => 'active',
                'features' => [
                    'appointments' => $contractualPlan->monthly_appointments_per_employee,
                ],
                'availableAppointments' => $user->monthly_appointments_left,
                'canCreateAppointment' => $user->canCreateAppointment(),
                'hasOngoingAppointment' => $user->hasOngoingAppointment(),
            ];
        }

        /** @var Subscription $subscription */
        $subscription = $user
            ->activeSubscription()
            ->with('price.plan')
            ->first();

        $price = $subscription->price;
        $plan = $price->plan;

        return [
            'planName' => $plan->name,
            'description' => $plan->description,
            'status' => $subscription->ends_at ? 'expired' : ($subscription->stripe_status === 'active' ? 'active' : 'inactive'),
            'features' => [
                'appointments' => $price->monthlyAppointments,
                'whatsapp_access' => $price->whatsappEnabled,
                'exclusive_materials' => $price->materialsEnabled,
            ],
            'availableAppointments' => $user->monthly_appointments_left,
            'canCreateAppointment' => $user->canCreateAppointment(),
            'hasOngoingAppointment' => $user->hasOngoingAppointment(),
        ];
    }

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
}

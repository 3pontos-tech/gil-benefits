<?php

namespace TresPontosTech\App\Filament\Widgets;

use App\Models\Users\User;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use TresPontosTech\App\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

class UserCurrentPlanWidget extends Widget
{
    protected string $view = 'filament.admin.widgets.plans-overview';

    protected int|string|array $columnSpan = 4;

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

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

    public function redirectToAppointmentCreation()
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user->canCreateAppointment()) {
            Notification::make()
                ->title(__('Não é possível agendar agora'))
                ->body(__('Você não possui agendamentos disponíveis neste mês ou já possui uma consultoria em andamento. Finalize a anterior para agendar outra.'))
                ->danger()
                ->send();

            return null;
        }

        return redirect()->intended(AppointmentResource::getUrl('create'));
    }
}

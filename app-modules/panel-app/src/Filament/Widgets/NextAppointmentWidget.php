<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Widgets;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Filament\Actions\CancelAppointmentAction;
use TresPontosTech\PanelApp\Filament\Concerns\ConfirmsAppointmentCancellation;
use TresPontosTech\PanelApp\Filament\Contracts\ShowsCancelledConfirmation;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\AppointmentResource;

class NextAppointmentWidget extends Widget implements HasActions, HasSchemas, ShowsCancelledConfirmation
{
    use ConfirmsAppointmentCancellation;
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.app.widgets.next-appointment';

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 3];

    protected function getViewData(): array
    {
        $appointment = $this->resolveAppointment();

        $hasConfirmedStatus = $appointment instanceof Appointment
            && $appointment->status === AppointmentStatus::Active;

        return [
            'appointment' => $appointment,
            'hasConfirmedStatus' => $hasConfirmedStatus,
            'listUrl' => AppointmentResource::getUrl('index'),
        ];
    }

    public function cancelAppointmentAction(): Action
    {
        return CancelAppointmentAction::make('cancelAppointment')
            ->link()
            ->record($this->resolveAppointment());
    }

    #[On('appointment-cancelled')]
    public function refresh(): void {}

    private function resolveAppointment(): ?Appointment
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->appointments()
            ->with('consultant')
            ->where(function (Builder $query): void {
                // Confirmada: continua relevante mesmo após o horário (consultoria em andamento/atrasada).
                $query->where('status', AppointmentStatus::Active->value)
                    // Aguardando confirmação: só enquanto ainda está por vir.
                    ->orWhere(function (Builder $pending): void {
                        $pending->where('status', AppointmentStatus::Pending->value)
                            ->where('appointment_at', '>=', now());
                    });
            })
            ->oldest('appointment_at')
            ->first();
    }
}

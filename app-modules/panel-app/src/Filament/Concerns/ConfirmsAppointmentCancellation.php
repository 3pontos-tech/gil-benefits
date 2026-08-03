<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * Fornece a tela de sucesso que a CancelAppointmentAction abre no lugar da
 * confirmação depois de cancelar. Vive num trait porque a action é usada em três
 * hosts (os dois widgets do dashboard e a listagem do resource) e o
 * replaceMountedAction precisa reencontrar a action por nome em cada request —
 * o que só acontece se o método existir no componente Livewire de cada host.
 *
 * O host que usa este trait deve implementar ShowsCancelledConfirmation e já
 * compor InteractsWithActions (de onde vem replaceMountedAction).
 */
trait ConfirmsAppointmentCancellation
{
    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>  $context
     */
    abstract public function replaceMountedAction(string $name, array $arguments = [], array $context = []): void;

    public function confirmAppointmentCancellation(Appointment $appointment): void
    {
        $this->replaceMountedAction('cancelledConfirmation', [
            'appointment' => $appointment->getKey(),
        ]);
    }

    public function cancelledConfirmationAction(): Action
    {
        return Action::make('cancelledConfirmation')
            ->modalIcon(Heroicon::OutlinedCheckCircle)
            ->modalIconColor('success')
            // Mesma largura da confirmação, para a troca de um modal pelo outro
            // não mudar a caixa sob o cursor.
            ->modalWidth(Width::FourExtraLarge)
            ->modalAlignment(Alignment::Start)
            ->modalHeading(__('panel-app::resources.appointments.cancel.confirmed.heading'))
            ->modalDescription(__('panel-app::resources.appointments.cancel.confirmed.description'))
            // Só um botão no rodapé, alinhado à direita como no layout.
            ->modalCancelAction(false)
            ->modalFooterActionsAlignment(Alignment::End)
            ->modalSubmitActionLabel(__('panel-app::resources.appointments.cancel.confirmed.finish'))
            ->extraModalWindowAttributes(['class' => 'fi-cancelled-confirmation-modal'])
            ->modalContent(fn (array $arguments): ?View => $this->cancelledConfirmationContent($arguments))
            // O único papel do "Finalizar" é fechar; o cancelamento já ocorreu.
            ->action(fn (): null => null);
    }

    /**
     * Os argumentos de uma action montada chegam do cliente, então nada aqui
     * confia neles: a consulta é restrita ao usuário autenticado e precisa
     * estar de fato cancelada, e o destino do crédito sai do status persistido
     * (Cancelled devolve, CancelledLate consome) em vez de vir do payload.
     *
     * @param  array<string, mixed>  $arguments
     */
    private function cancelledConfirmationContent(array $arguments): ?View
    {
        $appointment = Appointment::query()
            ->whereKey($arguments['appointment'] ?? null)
            ->where('user_id', auth()->id())
            ->first();

        if (! $appointment instanceof Appointment) {
            return null;
        }

        if (! in_array($appointment->status, [AppointmentStatus::Cancelled, AppointmentStatus::CancelledLate], strict: true)) {
            return null;
        }

        return view('filament.app.appointments.cancelled-confirmation-modal', [
            'appointment' => $appointment,
            'keepsCredit' => $appointment->status === AppointmentStatus::Cancelled,
        ]);
    }
}

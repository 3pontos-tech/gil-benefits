<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
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

    public function confirmAppointmentCancellation(Appointment $appointment, bool $keepsCredit): void
    {
        $this->replaceMountedAction('cancelledConfirmation', [
            'appointment' => $appointment->getKey(),
            'keepsCredit' => $keepsCredit,
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
     * @param  array<string, mixed>  $arguments
     */
    private function cancelledConfirmationContent(array $arguments): ?View
    {
        $appointment = Appointment::query()->find($arguments['appointment'] ?? null);

        if (! $appointment instanceof Appointment) {
            return null;
        }

        return view('filament.app.appointments.cancelled-confirmation-modal', [
            'appointment' => $appointment,
            'keepsCredit' => (bool) ($arguments['keepsCredit'] ?? false),
        ]);
    }
}

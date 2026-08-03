<?php

namespace TresPontosTech\PanelApp\Filament\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Filament\Contracts\ShowsCancelledConfirmation;

class CancelAppointmentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cancel-appointment';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('panel-app::resources.appointments.cancel.action_label'));
        $this->icon(Heroicon::XMark);
        $this->color('danger');

        $this->visible(fn (Appointment $record): bool => in_array($record->status, [
            AppointmentStatus::Pending,
            AppointmentStatus::Active,
        ], strict: true) && $record->appointment_at->isFuture());

        $this->requiresConfirmation();

        $this->modalIcon(Heroicon::OutlinedCalendarDays);
        $this->modalIconColor('danger');
        // No layout o título de 32px ocupa cerca de metade da largura do modal,
        // o que coloca a janela perto de 900px — 2xl (672px) ficava apertado.
        $this->modalWidth(Width::FourExtraLarge);
        // Start em vez do centro padrão: o layout põe o ícone à esquerda do
        // título, com título e descrição alinhados à esquerda.
        $this->modalAlignment(Alignment::Start);
        $this->modalHeading(__('panel-app::resources.appointments.cancel.modal_heading'));
        $this->modalDescription(__('panel-app::resources.appointments.cancel.modal_description'));
        $this->modalSubmitActionLabel(__('panel-app::resources.appointments.cancel.modal_submit_label'));
        $this->modalCancelActionLabel(__('panel-app::resources.appointments.cancel.modal_cancel_label'));

        // Classe só para o CSS do tema alcançar este modal sem atingir os demais.
        $this->extraModalWindowAttributes(['class' => 'fi-cancel-appointment-modal']);

        $this->modalContent(fn (Appointment $record): View => view(
            'filament.app.appointments.cancel-modal',
            [
                'appointment' => $record,
                // O aviso muda conforme o crédito volte ou não, e o prazo citado
                // vem da mesma constante que decide isso.
                'keepsCredit' => AppointmentStatus::resolveCancellationStatus($record, CancellationActor::User)
                    === AppointmentStatus::Cancelled,
                'noticeHours' => AppointmentStatus::CANCELLATION_NOTICE_HOURS,
            ],
        ));

        $this->action(function (Appointment $record): void {
            if ($record->user_id !== auth()->id()) {
                return;
            }

            $record->current_transition->handle(new TransitionData(
                cancellationActor: CancellationActor::User,
                cancelledBy: auth()->user(),
            ));

            $livewire = $this->getLivewire();
            if (! $livewire instanceof Component) {
                return;
            }

            // Atualiza a lista por baixo antes de abrir a confirmação de sucesso.
            $livewire->dispatch('appointment-cancelled');

            // Onde o host oferece a tela de sucesso, ela substitui este modal;
            // do contrário caímos no toast para não deixar a ação sem retorno.
            if ($livewire instanceof ShowsCancelledConfirmation) {
                $livewire->confirmAppointmentCancellation($record);

                return;
            }

            Notification::make()
                ->title(__('panel-app::resources.appointments.cancel.success'))
                ->success()
                ->send();
        });
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Size;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Actions\SyncAppointmentScheduleAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\Schemas\PickSlotStep;

/**
 * Wizard de reagendamento em modal: consulta atual → nova data e hora →
 * resumo da alteração → sucesso. O horário vigente só muda na confirmação do
 * resumo; até lá o agendamento fica intacto, como o passo inicial promete.
 */
trait ReschedulesAppointments
{
    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>  $context
     */
    abstract public function replaceMountedAction(string $name, array $arguments = [], array $context = []): void;

    public function rescheduleAppointmentAction(): Action
    {
        return Action::make('rescheduleAppointment')
            ->label(__('panel-app::resources.appointments.reschedule.action_label'))
            ->color('gray')
            ->outlined()
            ->size(Size::Small)
            ->extraAttributes(['class' => 'text-[16px]'])
            ->modalIcon(Heroicon::OutlinedCalendarDays)
            ->modalIconColor('danger')
            ->modalWidth(Width::FourExtraLarge)
            ->modalAlignment(Alignment::Start)
            ->modalHeading(__('panel-app::resources.appointments.reschedule.intro.heading'))
            ->modalDescription(__('panel-app::resources.appointments.reschedule.intro.description', [
                'hours' => AppointmentStatus::RESCHEDULE_NOTICE_HOURS,
            ]))
            ->modalSubmitActionLabel(__('panel-app::resources.appointments.reschedule.next'))
            ->modalCancelActionLabel(__('panel-app::resources.appointments.cancel.modal_cancel_label'))
            ->extraModalWindowAttributes(['class' => 'fi-apt-wizard-modal fi-apt-wizard-modal-danger'])
            ->modalContent(function (array $arguments): ?View {
                $appointment = $this->resolveReschedulable($arguments);

                if (! $appointment instanceof Appointment) {
                    return null;
                }

                return view('filament.app.appointments.wizard.reschedule-intro', [
                    'appointment' => $appointment,
                ]);
            })
            ->action(function (array $arguments): void {
                if (! $this->resolveReschedulable($arguments) instanceof Appointment) {
                    $this->notifyCannotReschedule();

                    return;
                }

                $this->replaceMountedAction('reschedulePickSlot', $arguments);
            });
    }

    public function reschedulePickSlotAction(): Action
    {
        return Action::make('reschedulePickSlot')
            ->modalIcon(Heroicon::OutlinedCalendarDays)
            ->modalIconColor('danger')
            ->modalWidth(Width::FourExtraLarge)
            ->modalAlignment(Alignment::Start)
            ->modalHeading(__('panel-app::resources.appointments.reschedule.slot.heading'))
            ->modalDescription(__('panel-app::resources.appointments.reschedule.slot.description'))
            ->modalSubmitActionLabel(__('panel-app::resources.appointments.reschedule.next'))
            ->modalCancelActionLabel(__('panel-app::resources.appointments.cancel.modal_cancel_label'))
            ->extraModalWindowAttributes(['class' => 'fi-apt-wizard-modal fi-apt-wizard-modal-danger'])
            ->modalContent(function (array $arguments): ?View {
                $appointment = $this->resolveReschedulable($arguments);

                if (! $appointment instanceof Appointment) {
                    return null;
                }

                return view('filament.app.appointments.wizard.current-appointment-card', [
                    'appointment' => $appointment,
                ]);
            })
            ->schema(PickSlotStep::fields())
            ->action(function (array $data, array $arguments): void {
                if (! $this->resolveReschedulable($arguments) instanceof Appointment) {
                    $this->notifyCannotReschedule();

                    return;
                }

                $this->replaceMountedAction('rescheduleReview', [
                    ...$arguments,
                    'appointment_at' => $data['appointment_at'],
                ]);
            });
    }

    public function rescheduleReviewAction(): Action
    {
        return Action::make('rescheduleReview')
            ->modalIcon(Heroicon::OutlinedClock)
            ->modalIconColor('danger')
            ->modalWidth(Width::TwoExtraLarge)
            ->modalAlignment(Alignment::Start)
            ->modalHeading(__('panel-app::resources.appointments.reschedule.review.heading'))
            ->modalDescription(__('panel-app::resources.appointments.reschedule.review.description'))
            ->modalSubmitActionLabel(__('panel-app::resources.appointments.reschedule.review.submit'))
            ->modalCancelActionLabel(__('panel-app::resources.appointments.cancel.modal_cancel_label'))
            ->extraModalWindowAttributes(['class' => 'fi-apt-wizard-modal fi-apt-wizard-modal-danger'])
            ->modalContent(fn (array $arguments): View => view(
                'filament.app.appointments.wizard.review-rows',
                [
                    'rows' => $this->rescheduleReviewRows($arguments),
                    'notice' => __('panel-app::resources.appointments.reschedule.review.notice'),
                ],
            ))
            ->action(function (array $arguments): void {
                $appointment = $this->resolveReschedulable($arguments);

                if (! $appointment instanceof Appointment) {
                    $this->notifyCannotReschedule();

                    return;
                }

                $newAppointmentAt = Date::parse($arguments['appointment_at']);
                $previousAppointmentAt = $appointment->appointment_at;
                $previousConsultantId = $appointment->consultant_id;

                $appointment->update(['appointment_at' => $newAppointmentAt]);

                try {
                    // Reaproveita o mesmo pós-processamento do painel admin:
                    // histórico de reagendamento, re-bloqueio da agenda e Google
                    // Calendar. Num horário indisponível a action reverte o
                    // registro antes de relançar.
                    resolve(SyncAppointmentScheduleAction::class)
                        ->handle($appointment, $previousConsultantId, $previousAppointmentAt);
                } catch (SlotUnavailableException) {
                    Notification::make()
                        ->title(__('panel-app::resources.appointments.reschedule.slot_unavailable'))
                        ->danger()
                        ->send();

                    return;
                }

                $this->dispatch('appointment-rescheduled');

                $this->replaceMountedAction('rescheduleConfirmed', [
                    'appointment' => $appointment->getKey(),
                    'previous_at' => $previousAppointmentAt->toDateTimeString(),
                ]);
            });
    }

    public function rescheduleConfirmedAction(): Action
    {
        return Action::make('rescheduleConfirmed')
            ->modalIcon(Heroicon::OutlinedCheckCircle)
            ->modalIconColor('success')
            ->modalWidth(Width::Large)
            ->modalAlignment(Alignment::Start)
            ->modalHeading(__('panel-app::resources.appointments.reschedule.confirmed.heading'))
            ->modalDescription(__('panel-app::resources.appointments.reschedule.confirmed.description'))
            ->modalSubmitActionLabel(__('panel-app::resources.appointments.reschedule.confirmed.finish'))
            ->modalCancelAction(false)
            ->modalFooterActionsAlignment(Alignment::End)
            ->extraModalWindowAttributes(['class' => 'fi-apt-wizard-modal fi-apt-wizard-modal-success fi-apt-wizard-modal-footer-end'])
            ->modalContent(function (array $arguments): ?View {
                $appointment = $this->resolveOwnedAppointment($arguments);

                if (! $appointment instanceof Appointment || blank($arguments['previous_at'] ?? null)) {
                    return null;
                }

                return view('filament.app.appointments.wizard.reschedule-confirmed', [
                    'previousAt' => Date::parse($arguments['previous_at']),
                    'newAt' => $appointment->appointment_at,
                ]);
            })
            // Fechar é o único papel do botão; o reagendamento já aconteceu.
            ->action(fn (): null => null);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<int, array{icon: string, label: string, value: string, highlight?: bool}>
     */
    private function rescheduleReviewRows(array $arguments): array
    {
        $appointment = $this->resolveOwnedAppointment($arguments);
        $newAppointmentAt = Date::parse($arguments['appointment_at']);

        return [
            [
                'icon' => 'heroicon-o-tag',
                'label' => __('panel-app::resources.appointments.reschedule.review.category'),
                'value' => $appointment?->category_type->getLabel() ?? '-',
            ],
            [
                'icon' => 'heroicon-o-calendar',
                'label' => __('panel-app::resources.appointments.reschedule.review.new_date'),
                'value' => $newAppointmentAt->format('d/m/Y'),
                'highlight' => true,
            ],
            [
                'icon' => 'heroicon-o-clock',
                'label' => __('panel-app::resources.appointments.reschedule.review.new_time'),
                'value' => $newAppointmentAt->format('H:i'),
                'highlight' => true,
            ],
            [
                'icon' => 'heroicon-o-clock',
                'label' => __('panel-app::resources.appointments.reschedule.review.duration'),
                'value' => __('panel-app::resources.appointments.reschedule.review.duration_value'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function resolveOwnedAppointment(array $arguments): ?Appointment
    {
        return Appointment::query()
            ->whereKey($arguments['appointment'] ?? null)
            ->where('user_id', auth()->id())
            ->first();
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    private function resolveReschedulable(array $arguments): ?Appointment
    {
        $appointment = $this->resolveOwnedAppointment($arguments);

        if (! $appointment instanceof Appointment || ! $appointment->canBeRescheduledByUser()) {
            return null;
        }

        return $appointment;
    }

    private function notifyCannotReschedule(): void
    {
        Notification::make()
            ->title(__('panel-app::resources.appointments.reschedule.cannot_reschedule'))
            ->danger()
            ->send();
    }
}

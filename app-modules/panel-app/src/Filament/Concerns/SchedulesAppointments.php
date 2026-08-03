<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Concerns;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Cancel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Date;
use Throwable;
use TresPontosTech\Appointments\Actions\BookAppointmentAction;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\Schemas\PickSlotStep;

/**
 * Wizard de agendamento em modal: categoria → data e hora → revisão → sucesso.
 * Cada passo é uma action própria e o avanço troca a action montada, então o
 * host precisa expor os quatro métodos para o Filament reencontrá-las a cada
 * request — é por isso que vivem num trait e não numa classe de action.
 */
trait SchedulesAppointments
{
    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>  $context
     */
    abstract public function replaceMountedAction(string $name, array $arguments = [], array $context = []): void;

    public function scheduleAppointmentAction(): Action
    {
        return Action::make('scheduleAppointment')
            ->label(__('panel-app::resources.appointments.schedule.action_label'))
            ->button()
            ->extraAttributes(['class' => 'shrink-0'])
            ->modalWidth(Width::FourExtraLarge)
            ->modalAlignment(Alignment::Start)
            ->modalHeading(__('panel-app::resources.appointments.schedule.category.heading'))
            ->modalDescription(__('panel-app::resources.appointments.schedule.category.description'))
            ->modalSubmitActionLabel(__('panel-app::resources.appointments.schedule.next'))
            ->modalCancelActionLabel(__('panel-app::resources.appointments.cancel.modal_cancel_label'))
            ->extraModalWindowAttributes(['class' => 'fi-apt-wizard-modal'])
            ->mountUsing(function (Schema $schema): void {
                /** @var User $user */
                $user = auth()->user();

                if (! $user->canCreateAppointment()) {
                    $this->notifyCannotBook();

                    throw new Cancel;
                }

                $schema->fill();
            })
            ->schema([
                ViewField::make('category_type')
                    ->label(__('appointments::resources.appointments.wizard.labels.category_type'))
                    ->hiddenLabel()
                    ->view('filament.app.appointments.wizard.category-field')
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->replaceMountedAction('schedulePickSlot', [
                    'category_type' => $data['category_type'],
                ]);
            });
    }

    public function schedulePickSlotAction(): Action
    {
        return Action::make('schedulePickSlot')
            ->modalIcon(Heroicon::OutlinedCalendarDays)
            ->modalIconColor('gray')
            ->modalWidth(Width::FourExtraLarge)
            ->modalAlignment(Alignment::Start)
            ->modalHeading(__('panel-app::resources.appointments.schedule.slot.heading'))
            ->modalDescription(__('panel-app::resources.appointments.schedule.slot.description'))
            ->modalSubmitActionLabel(__('panel-app::resources.appointments.schedule.next'))
            ->modalCancelActionLabel(__('panel-app::resources.appointments.cancel.modal_cancel_label'))
            ->extraModalWindowAttributes(['class' => 'fi-apt-wizard-modal'])
            ->schema(PickSlotStep::fields())
            ->action(function (array $data, array $arguments): void {
                $this->replaceMountedAction('scheduleReview', [
                    ...$arguments,
                    'appointment_at' => $data['appointment_at'],
                ]);
            });
    }

    public function scheduleReviewAction(): Action
    {
        return Action::make('scheduleReview')
            ->modalIcon(Heroicon::OutlinedCheckCircle)
            ->modalIconColor('gray')
            ->modalWidth(Width::TwoExtraLarge)
            ->modalAlignment(Alignment::Start)
            ->modalHeading(__('panel-app::resources.appointments.schedule.review.heading'))
            ->modalDescription(__('panel-app::resources.appointments.schedule.review.description'))
            ->modalSubmitActionLabel(__('panel-app::resources.appointments.schedule.review.submit'))
            ->modalCancelActionLabel(__('panel-app::resources.appointments.cancel.modal_cancel_label'))
            ->extraModalWindowAttributes(['class' => 'fi-apt-wizard-modal'])
            ->modalContent(fn (array $arguments): View => view(
                'filament.app.appointments.wizard.review-rows',
                [
                    'rows' => $this->scheduleReviewRows($arguments),
                    'notice' => __('panel-app::resources.appointments.schedule.review.notice', [
                        'hours' => Appointment::RESCHEDULE_WINDOW_HOURS,
                    ]),
                ],
            ))
            ->action(function (array $arguments): void {
                /** @var User $user */
                $user = auth()->user();

                // O saldo pode ter mudado entre abrir o wizard e confirmar.
                if (! $user->canCreateAppointment()) {
                    $this->notifyCannotBook();

                    return;
                }

                try {
                    resolve(BookAppointmentAction::class)->handle(BookAppointmentDTO::make($user->getKey(), [
                        'category_type' => $arguments['category_type'],
                        'appointment_at' => $arguments['appointment_at'],
                    ]));
                } catch (Throwable) {
                    Notification::make()
                        ->title(__('panel-app::resources.appointments.pages.create.booking_failed'))
                        ->danger()
                        ->send();

                    return;
                }

                $this->dispatch('appointment-booked');

                $this->replaceMountedAction('scheduleConfirmed', $arguments);
            });
    }

    public function scheduleConfirmedAction(): Action
    {
        return Action::make('scheduleConfirmed')
            ->modalIcon(Heroicon::OutlinedCheckCircle)
            ->modalIconColor('success')
            ->modalWidth(Width::Large)
            ->modalAlignment(Alignment::Start)
            ->modalHeading(__('panel-app::resources.appointments.schedule.confirmed.heading'))
            ->modalDescription(__('panel-app::resources.appointments.schedule.confirmed.description'))
            ->modalSubmitActionLabel(__('panel-app::resources.appointments.schedule.confirmed.back_home'))
            ->modalCancelAction(false)
            ->extraModalWindowAttributes(['class' => 'fi-apt-wizard-modal fi-apt-wizard-modal-success fi-apt-wizard-modal-footer-full'])
            ->modalContent(function (array $arguments): ?View {
                $category = AppointmentCategoryEnum::tryFrom($arguments['category_type'] ?? '');

                if (! $category instanceof AppointmentCategoryEnum) {
                    return null;
                }

                return view('filament.app.appointments.wizard.schedule-confirmed', [
                    'category' => $category,
                    'appointmentAt' => Date::parse($arguments['appointment_at']),
                ]);
            })
            // Fechar é o único papel do botão; o agendamento já foi criado.
            ->action(fn (): null => null);
    }

    /**
     * @param  array<string, mixed>  $arguments
     * @return array<int, array{icon: string, label: string, value: string, highlight?: bool}>
     */
    private function scheduleReviewRows(array $arguments): array
    {
        $category = AppointmentCategoryEnum::tryFrom($arguments['category_type'] ?? '');
        $appointmentAt = Date::parse($arguments['appointment_at']);

        return [
            [
                'icon' => 'heroicon-o-tag',
                'label' => __('panel-app::resources.appointments.schedule.review.category'),
                'value' => $category?->getLabel() ?? '-',
            ],
            [
                'icon' => 'heroicon-o-calendar',
                'label' => __('panel-app::resources.appointments.schedule.review.date'),
                'value' => $appointmentAt->format('d/m/Y'),
                'highlight' => true,
            ],
            [
                'icon' => 'heroicon-o-clock',
                'label' => __('panel-app::resources.appointments.schedule.review.time'),
                'value' => $appointmentAt->format('H:i'),
                'highlight' => true,
            ],
            [
                'icon' => 'heroicon-o-clock',
                'label' => __('panel-app::resources.appointments.schedule.review.duration'),
                'value' => __('panel-app::resources.appointments.schedule.review.duration_value'),
            ],
        ];
    }

    private function notifyCannotBook(): void
    {
        Notification::make()
            ->title(__('panel-app::resources.appointments.pages.create.cannot_book_now'))
            ->body(__('panel-app::resources.appointments.pages.create.no_appointments_available'))
            ->danger()
            ->send();
    }
}

<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Widgets;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Size;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\PanelApp\Filament\Actions\CancelAppointmentAction;
use TresPontosTech\PanelApp\Filament\Resources\Appointments\AppointmentResource;

class LatestAppointmentsWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /**
     * Quantas linhas cabem na coluna sem competir em altura com o card de plano.
     */
    private const LIMIT = 5;

    protected string $view = 'filament.app.widgets.latest-appointments';

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 7];

    protected function getViewData(): array
    {
        return [
            'rows' => $this->appointments()->map(fn (Appointment $appointment): array => $this->toRow($appointment)),
            'createUrl' => AppointmentResource::getUrl('create'),
            'listUrl' => AppointmentResource::getUrl('index'),
        ];
    }

    public function cancelAppointmentAction(): Action
    {
        return CancelAppointmentAction::make('cancelAppointment')
            // Na lista o cancelar é a opção secundária: contornado e neutro,
            // para não competir com o botão de status à direita.
            ->icon(null)
            ->color('gray')
            ->outlined()
            ->size(Size::Small)
            // Os tamanhos do fi-btn param em 14px; o 16px do layout vem daqui.
            ->extraAttributes(['class' => 'text-[16px]'])
            ->record(fn (array $arguments): ?Appointment => $this->appointments()
                ->firstWhere('id', $arguments['appointment'] ?? null));
    }

    #[On('appointment-cancelled')]
    public function refresh(): void
    {
        unset($this->cachedAppointments);
    }

    /**
     * @var Collection<int, Appointment>|null
     */
    private ?Collection $cachedAppointments = null;

    /**
     * @return Collection<int, Appointment>
     */
    private function appointments(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->cachedAppointments ??= $user->appointments()
            ->with('consultant')
            ->latest('appointment_at')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * Traduz o agendamento para o que a linha precisa desenhar, mantendo a
     * blade livre de regra de negócio.
     *
     * @return array<string, mixed>
     */
    private function toRow(Appointment $appointment): array
    {
        $consultant = $appointment->consultant;
        $isPast = $appointment->appointment_at->isPast();

        $isCancelled = in_array($appointment->status, [
            AppointmentStatus::Cancelled,
            AppointmentStatus::CancelledLate,
        ], strict: true);

        // Cancelada, ou marcada como pendente/confirmada e o horário já passou
        // sem virar concluída: nos dois casos o caminho adiante é remarcar.
        $needsRescheduling = $isCancelled
            || ($isPast && in_array($appointment->status, [
                AppointmentStatus::Pending,
                AppointmentStatus::Active,
            ], strict: true));

        return [
            'id' => $appointment->getKey(),
            'record' => $appointment,
            'month' => Str::upper(rtrim($appointment->appointment_at->translatedFormat('M'), '.')),
            'day' => $appointment->appointment_at->format('d'),
            'title' => $consultant !== null
                ? __('panel-app::widgets.latest_appointments.with_consultant', ['name' => $consultant->name])
                : $appointment->category_type->getLabel(),
            'schedule' => $appointment->appointment_at->format('d/m/Y · H\hi'),
            'status' => $appointment->status,
            'needsRescheduling' => $needsRescheduling,
            'isCompleted' => $appointment->status === AppointmentStatus::Completed,
            'isPending' => $appointment->status === AppointmentStatus::Pending && ! $needsRescheduling,
            'meetingUrl' => $appointment->status === AppointmentStatus::Active && ! $isPast
                ? $appointment->meeting_url
                : null,
        ];
    }
}

@php
    $actionType = $record->action_type;
    $oldValues = collect($record->old_values ?? []);
    $newValues = collect($record->new_values ?? []);

    $resolveConsultantName = function (?string $consultantId): string {
        if (blank($consultantId)) {
            return '—';
        }

        $consultant = \TresPontosTech\Consultants\Models\Consultant::query()->find($consultantId);

        return $consultant?->name ?? 'Desconhecido';
    };
@endphp

<div class="space-y-4">
    <x-filament::section
        :icon="$actionType->getIcon()"
        :icon-color="$actionType->getColor()"
    >
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Ação</dt>
                <dd class="text-gray-900 dark:text-white">{{ $actionType->getLabel() }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Realizado por</dt>
                <dd class="text-gray-900 dark:text-white">{{ $record->admin?->name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500 dark:text-gray-400">Data e hora</dt>
                <dd class="text-gray-900 dark:text-white">{{ $record->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
        </dl>
    </x-filament::section>

    @if($actionType === \TresPontosTech\Appointments\Enums\AppointmentHistoryActionType::ConsultantChanged)
        <x-filament::section heading="Alteração de Consultor">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Consultor anterior</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $resolveConsultantName($oldValues->get('consultant_id')) }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Consultor atual</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $resolveConsultantName($newValues->get('consultant_id')) }}</dd>
                </div>
            </dl>
        </x-filament::section>

    @elseif($actionType === \TresPontosTech\Appointments\Enums\AppointmentHistoryActionType::ConsultantLeft)
        <x-filament::section heading="Consultor Removido">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Consultor</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $resolveConsultantName($oldValues->get('consultant_id')) }}</dd>
                </div>
            </dl>
        </x-filament::section>

    @elseif($actionType === \TresPontosTech\Appointments\Enums\AppointmentHistoryActionType::ConsultantAssigned)
        <x-filament::section heading="Consultor Atribuído">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Consultor</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $resolveConsultantName($newValues->get('consultant_id')) }}</dd>
                </div>
            </dl>
        </x-filament::section>

    @elseif($actionType === \TresPontosTech\Appointments\Enums\AppointmentHistoryActionType::ReScheduled)
        <x-filament::section heading="Reagendamento">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Data anterior</dt>
                    <dd class="text-gray-900 dark:text-white">
                        {{ $oldValues->get('appointment_at') ? \Carbon\Carbon::parse($oldValues->get('appointment_at'))->format('d/m/Y H:i') : '—' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Nova data</dt>
                    <dd class="text-gray-900 dark:text-white">
                        {{ $newValues->get('appointment_at') ? \Carbon\Carbon::parse($newValues->get('appointment_at'))->format('d/m/Y H:i') : '—' }}
                    </dd>
                </div>
            </dl>
        </x-filament::section>
    @endif
</div>

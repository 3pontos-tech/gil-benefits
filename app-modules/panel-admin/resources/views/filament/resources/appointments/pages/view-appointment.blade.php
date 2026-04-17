<x-filament-panels::page>
    @php
        $appointment = $this->getRecord();
        $record = $appointment->record;
        $documents = $this->getEmployeeDocuments();
        $sharedDocuments = $this->getSharedDocuments();
    @endphp

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-panel-admin::appointments.participants :appointment="$appointment" />
            @if($appointment->user?->anamnese)
                <x-panel-admin::appointments.anamnese :anamnese="$appointment->user->anamnese" />
            @endif
            <x-panel-admin::appointments.notes :notes="$appointment->notes" />
            <x-panel-admin::appointments.record-tabs :record="$record" />
        </div>

        <div class="space-y-6">
            <x-panel-admin::appointments.schedule :appointment="$appointment" />
            <x-panel-admin::appointments.documents :documents="$documents" :sharedDocuments="$sharedDocuments" />
            <x-panel-admin::appointments.ai-metadata :record="$record" />
            <x-panel-admin::appointments.metadata :appointment="$appointment" />
        </div>
    </div>
</x-filament-panels::page>

@php
    use TresPontosTech\Admin\Filament\Resources\Consultants\RelationManagers\SchedulesRelationManager;
    use  TresPontosTech\Consultants\Models\Consultant;
 @endphp
<x-filament-panels::page>
    @livewire(SchedulesRelationManager::class, [
         'ownerRecord' => Consultant::query()->where('user_id', auth()->user()->getKey())->first(),
         'pageClass' => static::class,
     ])
</x-filament-panels::page>

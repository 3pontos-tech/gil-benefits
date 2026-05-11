@php
    use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
    use TresPontosTech\Billing\Core\Models\UserCredit;

    $stats = UserCredit::query()
        ->where('holder_id', auth()->id())
        ->where('company_id', filament()->getTenant()?->getKey())
        ->whereNull('deleted_at')
        ->selectRaw('status, count(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status');

    $available = $stats[UserCreditStatusEnum::Available->value] ?? 0;
    $inUse     = $stats[UserCreditStatusEnum::InUse->value] ?? 0;
    $used      = $stats[UserCreditStatusEnum::Used->value] ?? 0;
    $total     = $available + $inUse + $used;
@endphp

<div class="space-y-6 py-6">

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <x-filament::section>
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium mb-1">
                Total
            </p>
            <p class="text-3xl font-bold">{{ $total }}</p>
        </x-filament::section>

        <x-filament::section>
            <p class="text-xs text-emerald-600 dark:text-emerald-400 uppercase tracking-wide font-medium mb-1">
                {{ __('billing::enums.user_credit_status.available') }}
            </p>
            <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $available }}</p>
        </x-filament::section>

        <x-filament::section>
            <p class="text-xs text-blue-600 dark:text-blue-400 uppercase tracking-wide font-medium mb-1">
                {{ __('billing::enums.user_credit_status.in_use') }}
            </p>
            <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $inUse }}</p>
        </x-filament::section>

        <x-filament::section>
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium mb-1">
                {{ __('billing::enums.user_credit_status.used') }}
            </p>
            <p class="text-3xl font-bold text-gray-500 dark:text-gray-400">{{ $used }}</p>
        </x-filament::section>
    </div>

    {{ $this->table }}
</div>

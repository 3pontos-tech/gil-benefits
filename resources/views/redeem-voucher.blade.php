<x-filament-panels::page>
    <form wire:submit="redeem" class="max-w-xl">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                {{ __('panel-app::pages.redeem_voucher.submit') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

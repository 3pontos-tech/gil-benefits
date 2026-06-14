<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6 dark:border-white/10">
            <x-filament::button
                type="button"
                color="gray"
                x-on:click="window.history.back()"
            >
                {{ __('panel-app::profile.actions.cancel') }}
            </x-filament::button>

            <x-filament::button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                {{ __('panel-app::profile.actions.save') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>

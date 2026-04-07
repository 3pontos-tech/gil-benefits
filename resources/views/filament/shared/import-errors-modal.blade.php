<div
    x-data="{ open: false, errors: [] }"
    @import-errors.window="open = true; errors = $event.detail.errors"
    x-cloak
>
    <div
        x-show="open"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50" @click="open = false"></div>

        {{-- Modal --}}
        <div
            class="relative z-10 w-full max-w-2xl bg-white dark:bg-gray-900 rounded-xl shadow-2xl flex flex-col"
            style="max-height: 80vh"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-warning-500" />
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                            Erros na Importação
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="`${errors.length} erro(s) encontrado(s)`"></p>
                    </div>
                </div>
                <button
                    @click="open = false"
                    class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-800 transition"
                    aria-label="Fechar"
                >
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>

            {{-- Body --}}
            <div class="overflow-y-auto px-6 py-4 flex-1 space-y-2">
                <template x-for="(error, index) in errors" :key="index">
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-warning-50 dark:bg-warning-950/20 border border-warning-200 dark:border-warning-800">
                        <span class="shrink-0 inline-flex items-center justify-center px-2 py-0.5 rounded-full bg-warning-100 dark:bg-warning-900 text-warning-700 dark:text-warning-400 text-xs font-semibold whitespace-nowrap" x-text="error.row > 0 ? `Linha ${error.row}` : '—'"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="error.email !== 'N/A' ? error.email : '—'"></p>
                            <p class="text-sm text-gray-600 dark:text-gray-400" x-text="error.message"></p>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Footer --}}
            <div class="flex justify-end px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <button
                    @click="open = false"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition"
                >
                    Fechar
                </button>
            </div>
        </div>
    </div>
</div>

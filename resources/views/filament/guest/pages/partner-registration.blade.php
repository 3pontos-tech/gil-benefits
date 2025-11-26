<x-filament-panels::page>
    <div class="max-w-2xl mx-auto">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ $this->getTitle() }}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Preencha os dados abaixo para cadastrar um novo colaborador parceiro
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6">
            <form wire:submit="submit">
                {{ $this->form }}

                <div class="mt-6 flex justify-end">
                    {{ $this->getFormActions() }}
                </div>
            </form>
        </div>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Já possui uma conta? 
                <a href="/app/login" class="text-primary-600 hover:text-primary-500 font-medium">
                    Faça login aqui
                </a>
            </p>
        </div>
    </div>
</x-filament-panels::page>
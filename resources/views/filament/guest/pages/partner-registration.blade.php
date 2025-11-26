<x-filament-panels::page>
    <div class="max-w-2xl mx-auto">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ $this->getTitle() }}
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                @if($registrationSuccess)
                    Cadastro concluído com sucesso!
                @else
                    Preencha os dados abaixo para cadastrar um novo colaborador parceiro
                @endif
            </p>
        </div>

        @if($registrationSuccess)
            {{-- Success State --}}
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6 mb-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-green-800 dark:text-green-200">
                            Cadastro realizado com sucesso!
                        </h3>
                    </div>
                </div>
                
                <div class="text-green-700 dark:text-green-300 mb-6">
                    <p>{{ $successMessage }}</p>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                    <h4 class="font-medium text-blue-800 dark:text-blue-200 mb-2">Próximos passos:</h4>
                    <ol class="list-decimal list-inside text-blue-700 dark:text-blue-300 space-y-1">
                        <li>Você será redirecionado automaticamente para a página de login em alguns segundos</li>
                        <li>Use seu e-mail e senha para acessar a plataforma</li>
                        <li>Você terá acesso apenas ao painel de usuário da sua empresa</li>
                        <li>Em caso de dúvidas, entre em contato com o responsável da sua empresa</li>
                    </ol>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <button 
                        wire:click="redirectToLogin"
                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        Ir para Login Agora
                    </button>
                    
                    <a 
                        href="/"
                        class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
                    >
                        Voltar ao Início
                    </a>
                </div>
            </div>
        @else
            {{-- Registration Form --}}
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 {{ $isSubmitting ? 'opacity-75 pointer-events-none' : '' }}">
                @if($isSubmitting)
                    <div class="absolute inset-0 bg-white/50 dark:bg-gray-800/50 flex items-center justify-center rounded-lg z-10">
                        <div class="flex items-center space-x-3">
                            <svg class="animate-spin h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-primary-600 font-medium">Processando cadastro...</span>
                        </div>
                    </div>
                @endif

                <form wire:submit="submit">
                    {{ $this->form }}

                    <div class="mt-6 flex justify-end">
                        @foreach ($this->getFormActions() as $action)
                            {{ $action }}
                        @endforeach
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
        @endif
    </div>

    @if($isSubmitting)
        <script>
            // Prevent form resubmission during processing
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                }
            });
        </script>
    @endif
</x-filament-panels::page>
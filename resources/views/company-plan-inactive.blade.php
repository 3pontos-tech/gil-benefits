@props([
    'companyName' => '',
])

<div class="flex flex-col items-center gap-6 py-8 px-4 text-center">
    <div class="rounded-full bg-warning-100 p-4 dark:bg-warning-500/20">
        <x-filament::icon
            icon="heroicon-o-exclamation-triangle"
            class="h-10 w-10 text-warning-600 dark:text-warning-400"
        />
    </div>

    <div class="space-y-2">
        <h1 class="text-2xl font-bold text-gray-950 dark:text-white">
            {{ __('views.company_plan_inactive.heading') }}
        </h1>

        <p class="text-base text-gray-500 dark:text-gray-400">
            {{ __('views.company_plan_inactive.description', ['company' => $companyName]) }}
        </p>
    </div>

    <p class="max-w-md text-sm text-gray-500 dark:text-gray-400">
        {{ __('views.company_plan_inactive.instruction') }}
    </p>

    <form method="POST" action="{{ route('filament.app.auth.logout') }}">
        @csrf

        <x-filament::button
            type="submit"
            color="gray"
            icon="heroicon-m-arrow-left-start-on-rectangle"
        >
            {{ __('views.company_plan_inactive.logout') }}
        </x-filament::button>
    </form>
</div>

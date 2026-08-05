@php /** @var string $supportUrl */ @endphp

<div class="border border-gray-200 bg-white p-8 dark:border-white/10 dark:bg-gray-900">
    <p class="text-[24px] font-bold leading-tight text-gray-950 dark:text-white">
        {{ __('panel-app::profile.support.heading') }}
    </p>
    <p class="mt-4 text-[16px] text-gray-500 dark:text-gray-400">
        {{ __('panel-app::profile.support.description') }}
    </p>

    <a
        href="{{ $supportUrl }}"
        class="mt-8 flex items-center gap-3 border border-gray-200 p-2 transition hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5"
    >
        <x-filament::icon icon="lucide-headset" class="size-6 shrink-0 text-danger-500"/>
        <span class="text-[16px] font-bold text-gray-950 dark:text-white">
            {{ __('panel-app::profile.support.action') }}
        </span>
    </a>
</div>

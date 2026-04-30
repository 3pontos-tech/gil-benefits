@php
    $company = filament()->getTenant();
    $companyName = '<span class="font-bold">' . e($company?->name ?? '') . '</span>';
@endphp

<div class="relative flex items-center justify-center bg-warning-400 dark:bg-warning-600 px-4 py-2 text-sm font-medium text-warning-950 dark:text-white">
    <span class="text-center">
        {!! __('panel-admin::banners.admin_company_access', ['company' => $companyName]) !!}
    </span>
    <a
        href="{{ route('filament.admin.resources.companies.index') }}"
        class="absolute right-4 shrink-0 rounded-md bg-warning-700 dark:bg-warning-800 px-3 py-1 font-semibold text-white decoration-white/50 hover:bg-warning-800 dark:hover:bg-warning-900 transition"
    >
        {{ __('panel-admin::banners.return_to_admin') }}
    </a>
</div>

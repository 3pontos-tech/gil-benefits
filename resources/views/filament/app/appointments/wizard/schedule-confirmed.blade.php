@php
    use Illuminate\Support\Str;

    /**
     * @var \TresPontosTech\Appointments\Enums\AppointmentCategoryEnum $category
     * @var \Illuminate\Support\Carbon $appointmentAt
     */
@endphp

<div class="flex flex-col gap-8">
    <dl class="flex flex-col divide-y divide-gray-200 border border-gray-200 dark:divide-white/10 dark:border-white/10">
        <div class="flex items-start gap-3 px-4 py-3.5">
            <x-filament::icon icon="heroicon-o-tag" class="mt-1 size-4 shrink-0 text-gray-500 dark:text-gray-400"/>
            <div class="min-w-0">
                <dt class="text-[16px] font-bold text-gray-950 dark:text-white">{{ $category->getLabel() }}</dt>
                <dd class="mt-0.5 text-[14px] text-gray-500 dark:text-gray-400">
                    {{ __('panel-app::resources.appointments.schedule.confirmed.category_caption') }}
                </dd>
            </div>
        </div>

        <div class="flex items-start gap-3 px-4 py-3.5">
            <x-filament::icon icon="heroicon-o-calendar" class="mt-1 size-4 shrink-0 text-gray-500 dark:text-gray-400"/>
            <div class="min-w-0">
                <dt class="text-[16px] font-bold text-gray-950 dark:text-white">
                    {{ Str::ucfirst($appointmentAt->translatedFormat('l, F d, Y')) }}
                </dt>
                <dd class="mt-0.5 text-[14px] text-gray-500 dark:text-gray-400">{{ $appointmentAt->format('H:i') }}</dd>
            </div>
        </div>

        <div class="flex items-start gap-3 px-4 py-3.5">
            <x-filament::icon icon="heroicon-o-clock" class="mt-1 size-4 shrink-0 text-gray-500 dark:text-gray-400"/>
            <div class="min-w-0">
                <dt class="text-[16px] font-bold text-gray-950 dark:text-white">
                    {{ __('panel-app::resources.appointments.schedule.review.duration_value') }}
                </dt>
                <dd class="mt-0.5 text-[14px] text-gray-500 dark:text-gray-400">
                    {{ __('panel-app::resources.appointments.schedule.review.duration') }}
                </dd>
            </div>
        </div>
    </dl>

    <div class="flex items-center gap-2 text-success-600 dark:text-success-400">
        <x-filament::icon icon="heroicon-o-check-circle" class="size-5 shrink-0"/>
        <span class="text-[16px] font-medium">
            {{ __('panel-app::resources.appointments.schedule.confirmed.awaiting_confirmation') }}
        </span>
    </div>
</div>

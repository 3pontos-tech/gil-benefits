@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $statePath = $getStatePath();
    // Escolher outro dia invalida o horário: o caminho do irmão sai do próprio
    // caminho deste campo para funcionar tanto no modal quanto fora dele.
    $slotStatePath = Str::beforeLast($statePath, '.') . '.appointment_at';
    $selected = $getState();
    $minDate = $minDate instanceof Closure ? $minDate() : $minDate;

    $monthNames = collect(range(1, 12))
        ->map(fn (int $month): string => Str::ucfirst(Carbon::create(2026, $month, 1)->translatedFormat('F')))
        ->all();
    // 2026-01-05 é uma segunda: a semana desenhada começa nela, como no layout.
    $weekdayShort = collect(range(0, 6))
        ->map(fn (int $offset): string => Str::ucfirst(rtrim(Carbon::parse('2026-01-05')->addDays($offset)->translatedFormat('D'), '.')))
        ->all();
    $weekdayFull = collect(range(0, 6))
        ->map(fn (int $offset): string => Str::ucfirst(Carbon::parse('2026-01-04')->addDays($offset)->translatedFormat('l')))
        ->all();
@endphp

<div
    class="h-full border border-gray-200 p-4 dark:border-white/10"
    x-data="{
        selected: @js($selected),
        min: @js($minDate),
        monthNames: @js($monthNames),
        weekdayFull: @js($weekdayFull),
        viewYear: 0,
        viewMonth: 0,
        init() {
            const base = this.parse(this.selected ?? this.min)
            this.viewYear = base.getFullYear()
            this.viewMonth = base.getMonth()
        },
        parse(iso) {
            const [y, m, d] = iso.split('-').map(Number)
            return new Date(y, m - 1, d)
        },
        format(date) {
            const pad = (n) => String(n).padStart(2, '0')
            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
        },
        get headerDay() {
            return this.selected ? String(this.parse(this.selected).getDate()).padStart(2, '0') : '--'
        },
        get headerMonth() {
            return this.monthNames[this.viewMonth]
        },
        get headerWeekday() {
            return this.selected ? this.weekdayFull[this.parse(this.selected).getDay()] : ''
        },
        get cells() {
            const first = new Date(this.viewYear, this.viewMonth, 1)
            const offset = (first.getDay() + 6) % 7
            return Array.from({ length: 42 }, (_, index) => {
                const date = new Date(this.viewYear, this.viewMonth, 1 - offset + index)
                const iso = this.format(date)
                return {
                    iso,
                    day: date.getDate(),
                    inMonth: date.getMonth() === this.viewMonth,
                    disabled: iso < this.min,
                }
            })
        },
        previousMonth() {
            this.viewMonth === 0 ? (this.viewMonth = 11, this.viewYear--) : this.viewMonth--
        },
        nextMonth() {
            this.viewMonth === 11 ? (this.viewMonth = 0, this.viewYear++) : this.viewMonth++
        },
        select(cell) {
            if (cell.disabled) return
            this.selected = cell.iso
            $wire.set('{{ $slotStatePath }}', null, false)
            $wire.set('{{ $statePath }}', cell.iso)
        },
    }"
>
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-2">
            <span class="text-[32px] font-bold leading-none text-gray-950 dark:text-white" x-text="headerDay"></span>
            <span class="min-w-0">
                <span class="block text-[18px] font-bold leading-tight text-gray-950 dark:text-white" x-text="headerMonth"></span>
                <span class="block text-[13px] text-danger-500" x-text="headerWeekday"></span>
            </span>
        </div>

        <div class="flex items-center gap-1">
            <button type="button" x-on:click="previousMonth" class="flex size-7 items-center justify-center text-danger-500 hover:bg-gray-100 dark:hover:bg-white/5">
                <x-filament::icon icon="heroicon-m-chevron-left" class="size-4"/>
            </button>
            <button type="button" x-on:click="nextMonth" class="flex size-7 items-center justify-center text-danger-500 hover:bg-gray-100 dark:hover:bg-white/5">
                <x-filament::icon icon="heroicon-m-chevron-right" class="size-4"/>
            </button>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-7 gap-y-1 text-center">
        @foreach ($weekdayShort as $weekday)
            <span class="pb-1 text-[13px] font-medium text-gray-500 dark:text-gray-400">{{ $weekday }}</span>
        @endforeach

        <template x-for="cell in cells" :key="cell.iso">
            <button
                type="button"
                x-on:click="select(cell)"
                x-text="cell.day"
                :disabled="cell.disabled"
                :class="{
                    'bg-danger-500 font-semibold text-white': selected === cell.iso,
                    'text-gray-950 hover:bg-gray-100 dark:text-white dark:hover:bg-white/5': selected !== cell.iso && cell.inMonth && ! cell.disabled,
                    'text-gray-400 dark:text-gray-600': ! cell.inMonth || cell.disabled,
                    'cursor-not-allowed': cell.disabled,
                }"
                class="mx-auto flex size-8 items-center justify-center rounded-full text-[13px] transition"
            ></button>
        </template>
    </div>
</div>

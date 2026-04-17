<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-balance mb-2">
            {{ __('panel-app::anamnese.page.title') }}
        </h1>
        <p class="text-muted-foreground text-lg">
            {{ __('panel-app::anamnese.page.description') }}
        </p>
    </div>

    {{ $this->form }}

    <x-filament-actions::modals />
</div>

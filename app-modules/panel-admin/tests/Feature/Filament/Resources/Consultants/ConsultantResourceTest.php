<?php

declare(strict_types=1);

use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\PanelAdmin\Filament\Resources\Consultants\Pages\CreateConsultant;
use TresPontosTech\PanelAdmin\Filament\Resources\Consultants\Pages\EditConsultant;
use TresPontosTech\PanelAdmin\Filament\Resources\Consultants\Pages\ListConsultants;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
});

it('renders the consultants list page', function (): void {
    livewire(ListConsultants::class)
        ->assertOk();
});

it('hides the preview tab on the create form', function (): void {
    livewire(CreateConsultant::class)
        ->assertDontSee('preview::tab', escape: false);
});

it('shows the avatar upload field to SuperAdmin on the edit form', function (): void {
    $consultant = Consultant::factory()->create();

    livewire(EditConsultant::class, ['record' => $consultant->getRouteKey()])
        ->assertFormFieldExists('avatar');
});

describe('custo por consultoria', function (): void {
    it('mostra o valor em reais, com vírgula decimal', function (): void {
        // Sem ponto de milhar: ele é da máscara e vive só na tela.
        $consultant = Consultant::factory()->create(['cost_per_appointment_cents' => 123456]);

        livewire(EditConsultant::class, ['record' => $consultant->getRouteKey()])
            ->assertFormSet(['cost_per_appointment_cents' => '1234,56']);
    });

    it('grava em centavos o que foi digitado em reais', function (): void {
        $consultant = Consultant::factory()->create(['cost_per_appointment_cents' => null]);

        livewire(EditConsultant::class, ['record' => $consultant->getRouteKey()])
            ->fillForm(['cost_per_appointment_cents' => '120,00'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($consultant->refresh()->cost_per_appointment_cents)->toBe(12000);
    });

    it('aceita valor com milhar, que chega sem o ponto', function (): void {
        $consultant = Consultant::factory()->create(['cost_per_appointment_cents' => null]);

        // A máscara manda "1.234,56"; o Filament tira o ponto antes de validar.
        livewire(EditConsultant::class, ['record' => $consultant->getRouteKey()])
            ->fillForm(['cost_per_appointment_cents' => '1234,56'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($consultant->refresh()->cost_per_appointment_cents)->toBe(123456);
    });

    it('aceita valor inteiro, sem centavos', function (): void {
        $consultant = Consultant::factory()->create(['cost_per_appointment_cents' => null]);

        livewire(EditConsultant::class, ['record' => $consultant->getRouteKey()])
            ->fillForm(['cost_per_appointment_cents' => '95'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($consultant->refresh()->cost_per_appointment_cents)->toBe(9500);
    });

    it('deixa em branco quando o campo é esvaziado, para cair no custo padrão', function (): void {
        $consultant = Consultant::factory()->create(['cost_per_appointment_cents' => 9500]);

        livewire(EditConsultant::class, ['record' => $consultant->getRouteKey()])
            ->fillForm(['cost_per_appointment_cents' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($consultant->refresh()->cost_per_appointment_cents)->toBeNull();
    });

    it('recusa valor negativo', function (): void {
        $consultant = Consultant::factory()->create();

        livewire(EditConsultant::class, ['record' => $consultant->getRouteKey()])
            ->fillForm(['cost_per_appointment_cents' => '-50,00'])
            ->call('save')
            ->assertHasFormErrors(['cost_per_appointment_cents']);
    });

    it('recusa texto no lugar do valor', function (): void {
        $consultant = Consultant::factory()->create();

        livewire(EditConsultant::class, ['record' => $consultant->getRouteKey()])
            ->fillForm(['cost_per_appointment_cents' => 'noventa'])
            ->call('save')
            ->assertHasFormErrors(['cost_per_appointment_cents']);
    });
});

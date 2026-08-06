<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\PanelAdmin\Actions\AppointmentFeedbacks\ExportAppointmentFeedbacksCsv;
use TresPontosTech\PanelAdmin\Filament\Resources\AppointmentFeedbacks\AppointmentFeedbackResource;
use TresPontosTech\PanelAdmin\Filament\Resources\AppointmentFeedbacks\Pages\ListAppointmentFeedbacks;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

function exportedFeedbacksCsv(?Builder $query = null): string
{
    $response = resolve(ExportAppointmentFeedbacksCsv::class)->handle(
        $query ?? AppointmentFeedbackResource::getEloquentQuery()
    );

    expect($response->headers->get('content-disposition'))
        ->toContain(sprintf('avaliacoes_atendimentos_%s.csv', now()->toDateString()));

    ob_start();
    $response->sendContent();

    return (string) ob_get_clean();
}

it('exports the evaluations with headers and data', function (): void {
    $user = User::factory()->create(['name' => 'Maria Silva']);
    AppointmentFeedback::factory()->create([
        'user_id' => $user->id,
        'rating' => 5,
        'comment' => 'Atendimento excelente',
    ]);

    $csv = exportedFeedbacksCsv();

    expect($csv)->toContain('"Data da avaliação";Nota;Comentário;Beneficiário;Consultor;Empresa')
        ->toContain('Maria Silva')
        ->toContain('Atendimento excelente');
});

it('exports only the rows matching the given query', function (): void {
    AppointmentFeedback::factory()->create(['rating' => 5, 'comment' => 'Excelente']);
    AppointmentFeedback::factory()->create(['rating' => 1, 'comment' => 'Ruim']);

    $csv = exportedFeedbacksCsv(
        AppointmentFeedbackResource::getEloquentQuery()->where('rating', 5)
    );

    expect($csv)->toContain('Excelente')
        ->and($csv)->not->toContain('Ruim');
});

it('keeps a comment from running as a spreadsheet formula', function (): void {
    AppointmentFeedback::factory()->create([
        'rating' => 5,
        'comment' => '=HYPERLINK("http://exemplo.test","clique")',
    ]);

    $csv = exportedFeedbacksCsv();

    expect($csv)->toContain("'=HYPERLINK")
        ->and($csv)->not->toContain(';=HYPERLINK');
});

it('downloads the csv from the list page respecting the active filters', function (): void {
    AppointmentFeedback::factory()->create(['rating' => 5, 'comment' => 'Excelente']);
    AppointmentFeedback::factory()->create(['rating' => 1, 'comment' => 'Ruim']);

    livewire(ListAppointmentFeedbacks::class)
        ->filterTable('rating', [5])
        ->callAction('exportCsv')
        ->assertFileDownloaded(sprintf('avaliacoes_atendimentos_%s.csv', now()->toDateString()));
});

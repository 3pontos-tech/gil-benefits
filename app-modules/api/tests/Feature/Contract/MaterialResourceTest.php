<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

use function Pest\Laravel\getJson;

it('exposes every key the app reads from Material', function (): void {
    config()->set('media-library.disk_name', 'r2');
    Storage::fake('r2');
    $employee = actingAsApiEmployee();
    $consultant = Consultant::factory()->create();

    $withFile = Document::factory()->active()->create([
        'title' => 'Planilha',
        'documentable_type' => $consultant->getMorphClass(),
        'documentable_id' => $consultant->id,
        'created_at' => now()->subDay(),
    ]);
    $withFile->addMedia(UploadedFile::fake()->create('orcamento.xlsx', 50, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))
        ->toMediaCollection('documents');

    $link = Document::factory()->active()->create([
        'title' => 'Aula',
        'type' => 'link',
        'link' => 'https://example.com/aula',
        'documentable_type' => $consultant->getMorphClass(),
        'documentable_id' => $consultant->id,
    ]);

    foreach ([$withFile, $link] as $document) {
        DocumentShare::factory()->create([
            'document_id' => $document->id,
            'consultant_id' => $consultant->id,
            'employee_id' => $employee->id,
            'created_at' => $document->created_at,
        ]);
    }

    $response = getJson(route('api.v1.materials.index'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'title', 'type', 'link', 'file', 'uploaded_by', 'shared_by' => ['id', 'name'], 'shared_at', 'viewed_at', 'favorited_at']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('data.0.type', 'link')
        ->assertJsonPath('data.0.link', 'https://example.com/aula')
        ->assertJsonPath('data.0.file', null)
        ->assertJsonPath('data.1.type', 'xlsx')
        ->assertJsonStructure(['data' => [1 => ['file' => ['name', 'mime_type', 'size', 'url']]]]);

    expect($response->json('data.1.shared_at'))->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
});

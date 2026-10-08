<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Consultants\Enums\DocumentExtensionTypeEnum;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;
use TresPontosTech\Consultants\Models\DocumentUserState;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->travelTo('2026-10-08 10:00:00');
    config()->set('media-library.disk_name', 'r2');
    Storage::fake('r2');
    $this->employee = actingAsApiEmployee();
});

/**
 * Documento de um consultor compartilhado com a pessoa.
 *
 * @param  array<string, mixed>  $document
 * @param  array<string, mixed>  $share
 */
function sharedMaterial(User $employee, array $document = [], array $share = [], ?Consultant $consultant = null): Document
{
    $consultant ??= Consultant::factory()->create();

    $material = Document::factory()->active()->create([
        'documentable_type' => $consultant->getMorphClass(),
        'documentable_id' => $consultant->getKey(),
        ...$document,
    ]);

    DocumentShare::factory()->create([
        'document_id' => $material->getKey(),
        'consultant_id' => $consultant->getKey(),
        'employee_id' => $employee->getKey(),
        ...$share,
    ]);

    return $material;
}

/**
 * @param  array<string, mixed>  $attributes
 */
function ownMaterial(User $employee, array $attributes = []): Document
{
    return Document::factory()->active()->create([
        'documentable_type' => $employee->getMorphClass(),
        'documentable_id' => $employee->getKey(),
        ...$attributes,
    ]);
}

it('lists shared and own materials together, newest first', function (): void {
    $consultant = Consultant::factory()->create(['name' => 'Thomas Teles']);
    $shared = sharedMaterial($this->employee, share: ['created_at' => now()->subDays(1)], consultant: $consultant);
    $own = ownMaterial($this->employee, ['created_at' => now()->subHours(2)]);
    $older = sharedMaterial($this->employee, share: ['created_at' => now()->subDays(10)]);

    getJson(route('api.v1.materials.index'))
        ->assertOk()
        ->assertJsonPath('data.*.id', [$own->id, $shared->id, $older->id])
        ->assertJsonPath('data.0.uploaded_by', 'employee')
        ->assertJsonPath('data.0.shared_by', ['id' => $this->employee->id, 'name' => $this->employee->name])
        ->assertJsonPath('data.1.uploaded_by', 'consultant')
        ->assertJsonPath('data.1.shared_by', ['id' => $consultant->id, 'name' => 'Thomas Teles'])
        ->assertJsonPath('data.1.shared_at', '2026-10-07T10:00:00-03:00');
});

it('hides what the employee should not see', function (): void {
    sharedMaterial($this->employee, share: ['active' => false]);
    sharedMaterial($this->employee, document: ['active' => false]);
    sharedMaterial(User::factory()->create());
    ownMaterial(User::factory()->create());
    sharedMaterial($this->employee)->delete();
    ownMaterial($this->employee)->delete();

    getJson(route('api.v1.materials.index'))
        ->assertOk()
        ->assertJsonPath('data', []);
});

it('shows a document shared by two consultants once, with the latest share', function (): void {
    $first = Consultant::factory()->create();
    $second = Consultant::factory()->create(['name' => 'Segunda Consultora']);
    $material = sharedMaterial($this->employee, share: ['created_at' => now()->subDays(5)], consultant: $first);

    DocumentShare::factory()->create([
        'document_id' => $material->getKey(),
        'consultant_id' => $second->getKey(),
        'employee_id' => $this->employee->getKey(),
        'created_at' => now()->subDay(),
    ]);

    getJson(route('api.v1.materials.index'))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.shared_by.name', 'Segunda Consultora')
        ->assertJsonPath('data.0.shared_at', '2026-10-07T10:00:00-03:00');
});

it('favorites and unfavorites, keeping the original date when favorited twice', function (): void {
    $material = sharedMaterial($this->employee);

    patchJson(route('api.v1.materials.favorite', $material->id), ['favorite' => true])
        ->assertOk()
        ->assertJsonPath('data.favorited_at', '2026-10-08T10:00:00-03:00');

    $this->travel(1)->hour();

    patchJson(route('api.v1.materials.favorite', $material->id), ['favorite' => true])
        ->assertJsonPath('data.favorited_at', '2026-10-08T10:00:00-03:00');

    patchJson(route('api.v1.materials.favorite', $material->id), ['favorite' => false])
        ->assertJsonPath('data.favorited_at', null);

    patchJson(route('api.v1.materials.favorite', $material->id), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['favorite']);
});

it('records the first view only', function (): void {
    $material = sharedMaterial($this->employee);

    getJson(route('api.v1.materials.index'))->assertJsonPath('data.0.viewed_at', null);

    patchJson(route('api.v1.materials.viewed', $material->id))
        ->assertOk()
        ->assertJsonPath('data.viewed_at', '2026-10-08T10:00:00-03:00');

    $this->travel(2)->days();

    patchJson(route('api.v1.materials.viewed', $material->id))
        ->assertJsonPath('data.viewed_at', '2026-10-08T10:00:00-03:00');

    expect(DocumentUserState::query()->count())->toBe(1);
});

it('keeps each employee state apart', function (): void {
    $material = sharedMaterial($this->employee);
    $other = User::factory()->create();
    DocumentShare::factory()->create(['document_id' => $material->id, 'employee_id' => $other->id]);
    DocumentUserState::query()->create(['document_id' => $material->id, 'user_id' => $other->id, 'favorited_at' => now(), 'viewed_at' => now()]);

    getJson(route('api.v1.materials.index'))
        ->assertJsonPath('data.0.favorited_at', null)
        ->assertJsonPath('data.0.viewed_at', null);
});

it('answers 404 for a material the employee cannot see', function (string $route, string $method): void {
    $foreign = ownMaterial(User::factory()->create());

    $this->json($method, route($route, $foreign->id), ['favorite' => true])->assertNotFound();
})->with([
    'favorite' => ['api.v1.materials.favorite', 'PATCH'],
    'viewed' => ['api.v1.materials.viewed', 'PATCH'],
    'delete' => ['api.v1.materials.destroy', 'DELETE'],
]);

it('uploads a material that is already seen by its owner', function (): void {
    $response = postJson(route('api.v1.materials.store'), [
        'title' => 'Extrato de setembro',
        'file' => UploadedFile::fake()->createWithContent('extrato.pdf', "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n"),
    ])->assertCreated();

    $response->assertJsonPath('data.title', 'Extrato de setembro')
        ->assertJsonPath('data.type', 'pdf')
        ->assertJsonPath('data.uploaded_by', 'employee')
        ->assertJsonPath('data.file.name', 'extrato.pdf')
        ->assertJsonPath('data.file.mime_type', 'application/pdf')
        ->assertJsonPath('data.viewed_at', '2026-10-08T10:00:00-03:00')
        ->assertJsonPath('data.shared_at', '2026-10-08T10:00:00-03:00');

    $document = Document::query()->findOrFail($response->json('data.id'));

    expect($document->isUploadedBy($this->employee))->toBeTrue()
        ->and($document->active)->toBeTrue()
        ->and($document->type)->toBe(DocumentExtensionTypeEnum::PDF)
        ->and($document->getMedia('documents'))->toHaveCount(1)
        ->and($response->json('data.file.url'))->toBeString()->not->toBeEmpty();
});

it('refuses an upload outside the accepted types or without a title', function (): void {
    postJson(route('api.v1.materials.store'), [
        'title' => 'Vídeo',
        'file' => UploadedFile::fake()->create('aula.mp4', 300, 'video/mp4'),
    ])->assertUnprocessable()->assertJsonValidationErrors(['file']);

    postJson(route('api.v1.materials.store'), [
        'file' => UploadedFile::fake()->create('extrato.pdf', 300, 'application/pdf'),
    ])->assertUnprocessable()->assertJsonValidationErrors(['title']);

    expect(Document::query()->count())->toBe(0);
});

it('deletes only the employee own materials', function (): void {
    $own = ownMaterial($this->employee);
    $shared = sharedMaterial($this->employee);

    deleteJson(route('api.v1.materials.destroy', $shared->id))->assertForbidden();
    deleteJson(route('api.v1.materials.destroy', $own->id))->assertNoContent();

    expect(Document::query()->find($own->id))->toBeNull()
        ->and(Document::withTrashed()->find($own->id))->not->toBeNull()
        ->and(Document::query()->find($shared->id))->not->toBeNull();
});

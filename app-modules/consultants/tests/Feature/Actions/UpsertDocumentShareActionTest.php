<?php

use App\Models\Users\User;
use TresPontosTech\Consultants\Actions\UpsertDocumentShareAction;
use TresPontosTech\Consultants\DTOs\DocumentShareDTO;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

use function Pest\Laravel\assertDatabaseHas;

describe('UpsertDocumentShareAction', function (): void {
    it('creates a new document share with active true', function (): void {
        $consultant = Consultant::factory()->create();
        $document = Document::factory()->forConsultant($consultant)->create();
        $employee = User::factory()->create();

        (new UpsertDocumentShareAction)->execute(
            new DocumentShareDTO($document->getKey(), $employee->getKey(), $consultant->getKey())
        );

        expect(DocumentShare::query()->count())->toBe(1);
        assertDatabaseHas(DocumentShare::class, [
            'document_id' => $document->getKey(),
            'employee_id' => $employee->getKey(),
            'consultant_id' => $consultant->getKey(),
            'active' => true,
        ]);
    });

    it('does not duplicate an existing share', function (): void {
        $share = DocumentShare::factory()
            ->for(Document::factory()->withLink())
            ->create();

        (new UpsertDocumentShareAction)->execute(
            new DocumentShareDTO($share->document_id, $share->employee_id, $share->consultant_id)
        );

        expect(
            DocumentShare::query()
                ->where('document_id', $share->document_id)
                ->where('employee_id', $share->employee_id)
                ->where('consultant_id', $share->consultant_id)
                ->count()
        )->toBe(1);
    });

    it('does not reactivate a deactivated share', function (): void {
        $share = DocumentShare::factory()
            ->for(Document::factory()->withLink())
            ->notActive()
            ->create();

        (new UpsertDocumentShareAction)->execute(
            new DocumentShareDTO($share->document_id, $share->employee_id, $share->consultant_id)
        );

        expect($share->fresh()->active)->toBeFalse();
    });
});

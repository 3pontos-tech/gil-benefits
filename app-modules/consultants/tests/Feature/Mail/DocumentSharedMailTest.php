<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Consultants\Mail\DocumentSharedMail;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;

describe('DocumentSharedMail', function (): void {
    it('has correct subject', function (): void {
        $consultant = Consultant::factory()->create();
        $document = Document::factory()->forConsultant($consultant)->create();
        $employee = User::factory()->create();

        $mailable = new DocumentSharedMail($document, $employee);

        $mailable->assertHasSubject(__('consultants::mail.document_shared.subject'));
    });

    it('renders employee name, document title and consultant name in HTML', function (): void {
        $consultant = Consultant::factory()->create(['name' => 'Ana Lima']);
        $document = Document::factory()->forConsultant($consultant)->create(['title' => 'Guia de Saude Mental']);
        $employee = User::factory()->create(['name' => 'Joao Silva']);

        $document->loadMissing('documentable');

        $mailable = new DocumentSharedMail($document, $employee);

        $mailable->assertSeeInHtml('Joao Silva');
        $mailable->assertSeeInHtml('Guia de Saude Mental');
        $mailable->assertSeeInHtml('Ana Lima');
    });

    it('renders generic message when documentable is absent', function (): void {
        $consultant = Consultant::factory()->create();
        $document = Document::factory()->forConsultant($consultant)->create();
        $employee = User::factory()->create();

        /** @var Document $documentWithoutOwner */
        $documentWithoutOwner = tap($document)->forceFill([
            'documentable_id' => null,
            'documentable_type' => null,
        ]);

        $mailable = new DocumentSharedMail($documentWithoutOwner, $employee);

        $mailable->assertSeeInHtml('Um novo documento foi compartilhado com você.');
    });

    it('is queued to the employee email', function (): void {
        Mail::fake();

        $consultant = Consultant::factory()->create();
        $document = Document::factory()->forConsultant($consultant)->create();
        $employee = User::factory()->create();

        Mail::to($employee->email)->queue(new DocumentSharedMail($document, $employee));

        Mail::assertQueued(
            DocumentSharedMail::class,
            fn (DocumentSharedMail $mail) => $mail->hasTo($employee->email),
        );
    });
});

<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Filament\Actions\DownloadDocumentFilamentAction;
use TresPontosTech\Consultants\Filament\Resources\Appointments\Pages\ViewAppointment;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->consultant = actingAsConsultant();

    $this->employee = User::factory()->create();

    $this->appointment = Appointment::factory()
        ->for($this->employee, 'user')
        ->for($this->consultant, 'consultant')
        ->create();
});

it('renders the view appointment page when the client has their own documents', function (): void {
    Storage::fake('r2');

    $document = Document::factory()->forUser($this->employee)->create();
    $document->addMedia(UploadedFile::fake()->create('file.pdf', 100, 'application/pdf'))
        ->toMediaCollection('documents');

    livewire(ViewAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertOk()
        ->assertSee($document->title);
});

it('renders the view appointment page when shared documents are present', function (): void {
    Storage::fake('r2');

    $document = Document::factory()->forConsultant($this->consultant)->create();
    $document->addMedia(UploadedFile::fake()->create('shared.pdf', 100, 'application/pdf'))
        ->toMediaCollection('documents');

    DocumentShare::factory()
        ->for($document, 'document')
        ->for($this->consultant, 'consultant')
        ->for($this->employee, 'employee')
        ->active()
        ->create();

    livewire(ViewAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertOk()
        ->assertSee($document->title);
});

it('exposes a temporary download url for a document with media', function (): void {
    Storage::fake('r2');

    $document = Document::factory()->forConsultant($this->consultant)->create();
    $document->addMedia(UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'))
        ->toMediaCollection('documents');

    $url = DownloadDocumentFilamentAction::make()
        ->record($document)
        ->getUrl();

    expect($url)->toBeString()->not->toBeEmpty();
});

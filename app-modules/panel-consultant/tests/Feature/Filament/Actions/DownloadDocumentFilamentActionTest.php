<?php

declare(strict_types=1);

use App\Models\Users\User;
use Filament\Support\Icons\Heroicon;
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

it('renders the appointment view with the client own documents listed', function (): void {
    Storage::fake('r2');

    $document = Document::factory()->forUser($this->employee)->create();
    $document->addMedia(UploadedFile::fake()->create('file.pdf', 100, 'application/pdf'))
        ->toMediaCollection('documents');

    livewire(ViewAppointment::class, ['record' => $this->appointment->getRouteKey()])
        ->assertOk()
        ->assertSee($document->title);
});

it('renders the appointment view with documents shared with the client', function (): void {
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

it('opens in a new tab, carries the download icon and exposes a temporary url', function (): void {
    Storage::fake('r2');

    $document = Document::factory()->forConsultant($this->consultant)->create();
    $document->addMedia(UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'))
        ->toMediaCollection('documents');

    $action = DownloadDocumentFilamentAction::make()->record($document);

    expect($action->getUrl())->toBeString()->not->toBeEmpty()
        ->and($action->shouldOpenUrlInNewTab())->toBeTrue()
        ->and($action->getIcon())->toBe(Heroicon::ArrowDown);
});

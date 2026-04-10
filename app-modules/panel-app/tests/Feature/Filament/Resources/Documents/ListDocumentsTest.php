<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\App\Filament\Resources\SharedDocuments\Pages\ListSharedDocuments;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('should render', function (): void {
    livewire(ListSharedDocuments::class)
        ->assertOk();
});

it('should render only shared documents', function (): void {
    $document = Document::factory()->forConsultant()->active()->create();
    DocumentShare::factory()
        ->for($document)
        ->for($this->employee, 'employee')
        ->for($document->documentable, 'consultant')
        ->active()
        ->create();

    $anotherDocument = Document::factory()->forConsultant()->active()->create();
    DocumentShare::factory()
        ->for($anotherDocument)
        ->for($anotherDocument->documentable, 'consultant')
        ->active()
        ->create();

    livewire(ListSharedDocuments::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$document])
        ->assertCanNotSeeTableRecords([$anotherDocument]);
});

test('user can not see not active document for him, but other users can see', function (): void {
    $document = Document::factory()->forConsultant()->active()->create();

    DocumentShare::factory()
        ->for($document)
        ->for($this->employee, 'employee')
        ->for($document->documentable, 'consultant')
        ->notActive()
        ->create();

    $anotherUser = User::factory()->create();
    $anotherUser->assignRole(Roles::Employee);
    DocumentShare::factory()
        ->for($document)
        ->for($anotherUser, 'employee')
        ->for($document->documentable, 'consultant')
        ->active()
        ->create();

    livewire(ListSharedDocuments::class)
        ->assertOk()
        ->assertCanNotSeeTableRecords([$document]);

    actingAs($anotherUser);

    livewire(ListSharedDocuments::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$document]);
});

test('no one can se a not active document', function (): void {
    $document = Document::factory()->forConsultant()->notActive()->create();

    DocumentShare::factory()
        ->for($document)
        ->for($this->employee, 'employee')
        ->for($document->documentable, 'consultant')
        ->active()
        ->create();

    $anotherUser = User::factory()->create();
    $anotherUser->assignRole(Roles::Employee);
    DocumentShare::factory()
        ->for($document)
        ->for($anotherUser, 'employee')
        ->for($document->documentable, 'consultant')
        ->active()
        ->create();

    livewire(ListSharedDocuments::class)
        ->assertOk()
        ->assertCanNotSeeTableRecords([$document]);

    actingAs($anotherUser);

    livewire(ListSharedDocuments::class)
        ->assertOk()
        ->assertCanNotSeeTableRecords([$document]);
});

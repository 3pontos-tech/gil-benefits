<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\App\Filament\Resources\SharedDocuments\Pages\ListSharedDocuments;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;

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
    $document = Document::factory()->active()->create();
    DocumentShare::factory()
        ->for($document)
        ->for($this->employee, 'employee')
        ->for($document->consultant, 'consultant')
        ->active()
        ->create();

    $anotherDocument = Document::factory()->active()->create();
    DocumentShare::factory()
        ->for($anotherDocument)
        ->for($anotherDocument->consultant, 'consultant')
        ->active()
        ->create();

    livewire(ListSharedDocuments::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$document])
        ->assertCanNotSeeTableRecords([$anotherDocument]);
});

it('should render only active documents', function (): void {
    $activeDocument = Document::factory()->active()->create();
    DocumentShare::factory()
        ->for($activeDocument)
        ->for($this->employee, 'employee')
        ->for($activeDocument->consultant, 'consultant')
        ->active()
        ->create();

    $notActiveDocument = Document::factory()->notActive()->create();
    DocumentShare::factory()
        ->for($notActiveDocument)
        ->for($this->employee, 'employee')
        ->for($notActiveDocument->consultant, 'consultant')
        ->notActive()
        ->create();

    livewire(ListSharedDocuments::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$activeDocument])
        ->assertCanNotSeeTableRecords([$notActiveDocument]);
});

test('user can not see not active document for him, but other users can see', function (): void {
    $document = Document::factory()->active()->create();

    DocumentShare::factory()
        ->for($document)
        ->for($this->employee, 'employee')
        ->for($document->consultant, 'consultant')
        ->notActive()
        ->create();

    $anotherUser = User::factory()->create();
    DocumentShare::factory()
        ->for($document)
        ->for($anotherUser, 'employee')
        ->for($document->consultant, 'consultant')
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
    $document = Document::factory()->notActive()->create();

    DocumentShare::factory()
        ->for($document)
        ->for($this->employee, 'employee')
        ->for($document->consultant, 'consultant')
        ->active()
        ->create();

    $anotherUser = User::factory()->create();
    DocumentShare::factory()
        ->for($document)
        ->for($anotherUser, 'employee')
        ->for($document->consultant, 'consultant')
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

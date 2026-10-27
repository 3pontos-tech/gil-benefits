<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Gate;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\Consultants\Models\DocumentShare;
use TresPontosTech\Permissions\PermissionsEnum;
use TresPontosTech\Permissions\Roles;

it('allows SuperAdmin to perform all actions', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Roles::SuperAdmin->value);

    $document = Document::factory()->withLink()->create();

    expect(Gate::forUser($user)->allows('viewAny', Document::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('view', $document))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', Document::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('update', $document))->toBeTrue()
        ->and(Gate::forUser($user)->allows('delete', $document))->toBeTrue()
        ->and(Gate::forUser($user)->allows('restore', $document))->toBeTrue()
        ->and(Gate::forUser($user)->allows('forceDelete', $document))->toBeTrue();
});

it('denies user without permissions on all actions', function (): void {
    $user = User::factory()->create();

    $document = Document::factory()->withLink()->create();

    expect(Gate::forUser($user)->denies('viewAny', Document::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('view', $document))->toBeTrue()
        ->and(Gate::forUser($user)->denies('create', Document::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('update', $document))->toBeTrue()
        ->and(Gate::forUser($user)->denies('delete', $document))->toBeTrue()
        ->and(Gate::forUser($user)->denies('restore', $document))->toBeTrue()
        ->and(Gate::forUser($user)->denies('forceDelete', $document))->toBeTrue();
});

it('allows user with viewAny permission to viewAny', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::ViewAny->buildPermissionFor(Document::class));

    expect(Gate::forUser($user)->allows('viewAny', Document::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('view', Document::factory()->withLink()->create()))->toBeTrue();
});

it('allows user with view permission to view', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::View->buildPermissionFor(Document::class));

    expect(Gate::forUser($user)->allows('view', Document::factory()->forUser($user)->withLink()->create()))->toBeTrue()
        ->and(Gate::forUser($user)->denies('create', Document::class))->toBeTrue();
});

it('allows user with create permission to create', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::Create->buildPermissionFor(Document::class));

    expect(Gate::forUser($user)->allows('create', Document::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('update', Document::factory()->withLink()->create()))->toBeTrue();
});

it('allows user with update permission to update', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::Update->buildPermissionFor(Document::class));

    expect(Gate::forUser($user)->allows('update', Document::factory()->forUser($user)->withLink()->create()))->toBeTrue()
        ->and(Gate::forUser($user)->denies('delete', Document::factory()->forUser($user)->withLink()->create()))->toBeTrue();
});

it('allows user with delete permission to delete', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::Delete->buildPermissionFor(Document::class));

    expect(Gate::forUser($user)->allows('delete', Document::factory()->forUser($user)->withLink()->create()))->toBeTrue()
        ->and(Gate::forUser($user)->denies('forceDelete', Document::factory()->forUser($user)->withLink()->create()))->toBeTrue();
});

it('allows user with restore permission to restore', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::Restore->buildPermissionFor(Document::class));

    expect(Gate::forUser($user)->allows('restore', Document::factory()->forUser($user)->withLink()->create()))->toBeTrue()
        ->and(Gate::forUser($user)->denies('update', Document::factory()->forUser($user)->withLink()->create()))->toBeTrue();
});

it('allows user with forceDelete permission to forceDelete', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::ForceDelete->buildPermissionFor(Document::class));

    expect(Gate::forUser($user)->allows('forceDelete', Document::factory()->forUser($user)->withLink()->create()))->toBeTrue()
        ->and(Gate::forUser($user)->denies('restore', Document::factory()->forUser($user)->withLink()->create()))->toBeTrue();
});

describe('ownership', function (): void {
    it('denies update and delete of a document uploaded by someone else even with the permissions', function (): void {
        $user = User::factory()->create();
        $user->givePermissionTo(
            PermissionsEnum::Update->buildPermissionFor(Document::class),
            PermissionsEnum::Delete->buildPermissionFor(Document::class),
            PermissionsEnum::Restore->buildPermissionFor(Document::class),
            PermissionsEnum::ForceDelete->buildPermissionFor(Document::class),
        );

        $document = Document::factory()->forUser()->withLink()->create();

        expect(Gate::forUser($user)->denies('update', $document))->toBeTrue()
            ->and(Gate::forUser($user)->denies('delete', $document))->toBeTrue()
            ->and(Gate::forUser($user)->denies('restore', $document))->toBeTrue()
            ->and(Gate::forUser($user)->denies('forceDelete', $document))->toBeTrue();
    });

    it('denies an employee to update a consultant material shared with them', function (): void {
        $employee = User::factory()->employee()->create();
        $document = Document::factory()->forConsultant()->active()->withLink()->create();
        DocumentShare::factory()
            ->for($document)
            ->for($employee, 'employee')
            ->for($document->documentable, 'consultant')
            ->active()
            ->create();

        expect(Gate::forUser($employee)->allows('view', $document))->toBeTrue()
            ->and(Gate::forUser($employee)->denies('update', $document))->toBeTrue()
            ->and(Gate::forUser($employee)->denies('delete', $document))->toBeTrue();
    });

    it('denies an employee to view a consultant material not shared with them', function (): void {
        $employee = User::factory()->employee()->create();
        $document = Document::factory()->forConsultant()->active()->withLink()->create();

        expect(Gate::forUser($employee)->denies('view', $document))->toBeTrue();
    });

    it('denies an employee to view a consultant material whose share was deactivated', function (): void {
        $employee = User::factory()->employee()->create();
        $document = Document::factory()->forConsultant()->active()->withLink()->create();
        DocumentShare::factory()
            ->for($document)
            ->for($employee, 'employee')
            ->for($document->documentable, 'consultant')
            ->notActive()
            ->create();

        expect(Gate::forUser($employee)->denies('view', $document))->toBeTrue();
    });

    it('allows the consultant to update and delete their own material', function (): void {
        $consultant = Consultant::factory()->create();
        $document = Document::factory()->forConsultant($consultant)->withLink()->create();

        expect(Gate::forUser($consultant->user)->allows('update', $document))->toBeTrue()
            ->and(Gate::forUser($consultant->user)->allows('delete', $document))->toBeTrue();
    });

    it('denies a consultant to update another consultant material', function (): void {
        $consultant = Consultant::factory()->create();
        $document = Document::factory()->forConsultant()->withLink()->create();

        expect(Gate::forUser($consultant->user)->denies('update', $document))->toBeTrue()
            ->and(Gate::forUser($consultant->user)->denies('delete', $document))->toBeTrue();
    });

    it('allows an admin with the permissions to update a document they do not own', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole(Roles::Admin->value);
        $admin->givePermissionTo(
            PermissionsEnum::Update->buildPermissionFor(Document::class),
            PermissionsEnum::Delete->buildPermissionFor(Document::class),
        );

        $document = Document::factory()->forConsultant()->withLink()->create();

        expect(Gate::forUser($admin)->allows('update', $document))->toBeTrue()
            ->and(Gate::forUser($admin)->allows('delete', $document))->toBeTrue();
    });
});

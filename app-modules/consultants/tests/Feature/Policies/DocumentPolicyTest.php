<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Gate;
use TresPontosTech\Consultants\Models\Document;
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

    expect(Gate::forUser($user)->allows('view', Document::factory()->withLink()->create()))->toBeTrue()
        ->and(Gate::forUser($user)->denies('create', Document::class))->toBeTrue();
});

it('allows user with create permission to create', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo(PermissionsEnum::Create->buildPermissionFor(Document::class));

    expect(Gate::forUser($user)->allows('create', Document::class))->toBeTrue()
        ->and(Gate::forUser($user)->denies('update', Document::factory()->withLink()->create()))->toBeTrue();
});

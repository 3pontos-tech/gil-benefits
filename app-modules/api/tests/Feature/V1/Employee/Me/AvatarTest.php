<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    config()->set('media-library.disk_name', 'r2');
    Storage::fake('r2');
});

it('uploads a photo and returns a temporary URL', function (): void {
    $employee = actingAsApiEmployee();

    $response = postJson(route('api.v1.me.avatar.store'), [
        'avatar' => UploadedFile::fake()->image('foto.jpg', 400, 400),
    ])->assertOk();

    expect($response->json('data.avatar_url'))->toBeString()->not->toBeEmpty()
        ->and($employee->getMedia('user_avatar'))->toHaveCount(1);
});

it('replaces the previous photo', function (): void {
    $employee = actingAsApiEmployee();

    postJson(route('api.v1.me.avatar.store'), ['avatar' => UploadedFile::fake()->image('a.png')])->assertOk();
    postJson(route('api.v1.me.avatar.store'), ['avatar' => UploadedFile::fake()->image('b.webp')])->assertOk();

    expect($employee->fresh()->getMedia('user_avatar'))->toHaveCount(1)
        ->and($employee->fresh()->getFirstMedia('user_avatar')->file_name)->toBe('b.webp');
});

it('rejects files that are not jpeg, png or webp', function (UploadedFile $file): void {
    actingAsApiEmployee();

    postJson(route('api.v1.me.avatar.store'), ['avatar' => $file])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['avatar']);
})->with([
    'pdf' => fn (): UploadedFile => UploadedFile::fake()->create('foto.pdf', 100, 'application/pdf'),
    'gif' => fn (): UploadedFile => UploadedFile::fake()->image('foto.gif'),
    'over 5 MB' => fn (): UploadedFile => UploadedFile::fake()->image('foto.jpg')->size(5121),
]);

it('removes the photo', function (): void {
    $employee = actingAsApiEmployee();
    postJson(route('api.v1.me.avatar.store'), ['avatar' => UploadedFile::fake()->image('foto.jpg')])->assertOk();

    deleteJson(route('api.v1.me.avatar.destroy'))
        ->assertOk()
        ->assertJsonPath('data.avatar_url', null);

    expect($employee->fresh()->getMedia('user_avatar'))->toHaveCount(0);
});

it('answers 200 when there is no photo to remove', function (): void {
    actingAsApiEmployee();

    deleteJson(route('api.v1.me.avatar.destroy'))
        ->assertOk()
        ->assertJsonPath('data.avatar_url', null);
});

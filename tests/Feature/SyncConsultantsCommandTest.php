<?php

use App\Models\Users\User;
use TresPontosTech\Consultants\Models\Consultant;

use function Pest\Laravel\assertDatabaseCount;

it('should create user for consultants that does not have an user associated', function () {

    $consultants = Consultant::factory(10)
        ->state(['user_id' => null])
        ->createQuietly();

    assertDatabaseCount(User::class, 0);
    expect($consultants->first()->user_id)->toBeNull();

    Artisan::call('app:sync-consultants');

    $consultants->each(function (Consultant $consultant) {
        $consultant->refresh();
        $user = $consultant->user;
        expect($consultant->user->exists())->toBeTrue()
            ->and($user->name)->toBe($consultant->name)
            ->and($user->email)->toBe($consultant->email);
    });
});

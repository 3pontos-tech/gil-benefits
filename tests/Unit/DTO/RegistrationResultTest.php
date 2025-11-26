<?php

use App\DTO\RegistrationResult;
use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;

describe('RegistrationResult', function () {
    it('can create a success result', function () {
        $user = Mockery::mock(User::class);
        $company = Mockery::mock(Company::class);

        $result = RegistrationResult::success($user, $company);

        expect($result->success)->toBeTrue();
        expect($result->user)->toBe($user);
        expect($result->company)->toBe($company);
        expect($result->error)->toBeNull();
        expect($result->isSuccess())->toBeTrue();
        expect($result->isFailure())->toBeFalse();
    });

    it('can create a failure result', function () {
        $errorMessage = 'Invalid partner code';

        $result = RegistrationResult::failure($errorMessage);

        expect($result->success)->toBeFalse();
        expect($result->user)->toBeNull();
        expect($result->company)->toBeNull();
        expect($result->error)->toBe($errorMessage);
        expect($result->isSuccess())->toBeFalse();
        expect($result->isFailure())->toBeTrue();
    });

    it('can be created with constructor', function () {
        $user = Mockery::mock(User::class);
        $company = Mockery::mock(Company::class);

        $result = new RegistrationResult(
            success: true,
            user: $user,
            company: $company,
            error: null
        );

        expect($result->success)->toBeTrue();
        expect($result->user)->toBe($user);
        expect($result->company)->toBe($company);
        expect($result->error)->toBeNull();
    });

    it('handles failure with custom error message', function () {
        $customError = 'Email already exists in the system';

        $result = RegistrationResult::failure($customError);

        expect($result->success)->toBeFalse();
        expect($result->error)->toBe($customError);
        expect($result->isFailure())->toBeTrue();
    });
});
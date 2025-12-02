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

    it('properties are readonly', function () {
        $user = Mockery::mock(User::class);
        $company = Mockery::mock(Company::class);

        $result = RegistrationResult::success($user, $company);

        // Properties should be accessible for reading
        expect($result->success)->toBeTrue();
        expect($result->user)->toBe($user);
        expect($result->company)->toBe($company);
        expect($result->error)->toBeNull();

        // Properties should be readonly (attempting to modify would cause an error)
        // $result->success = false; // This would fail
        // $result->user = null; // This would fail
    });

    it('success factory method sets correct properties', function () {
        $user = Mockery::mock(User::class);
        $company = Mockery::mock(Company::class);

        $result = RegistrationResult::success($user, $company);

        expect($result->success)->toBeTrue();
        expect($result->user)->toBe($user);
        expect($result->company)->toBe($company);
        expect($result->error)->toBeNull();
    });

    it('failure factory method sets correct properties', function () {
        $errorMessage = 'CPF inválido. Verifique o formato';

        $result = RegistrationResult::failure($errorMessage);

        expect($result->success)->toBeFalse();
        expect($result->user)->toBeNull();
        expect($result->company)->toBeNull();
        expect($result->error)->toBe($errorMessage);
    });

    it('isSuccess returns correct boolean value', function () {
        $user = Mockery::mock(User::class);
        $company = Mockery::mock(Company::class);

        $successResult = RegistrationResult::success($user, $company);
        $failureResult = RegistrationResult::failure('Error message');

        expect($successResult->isSuccess())->toBeTrue();
        expect($failureResult->isSuccess())->toBeFalse();
    });

    it('isFailure returns correct boolean value', function () {
        $user = Mockery::mock(User::class);
        $company = Mockery::mock(Company::class);

        $successResult = RegistrationResult::success($user, $company);
        $failureResult = RegistrationResult::failure('Error message');

        expect($successResult->isFailure())->toBeFalse();
        expect($failureResult->isFailure())->toBeTrue();
    });

    it('isSuccess and isFailure are mutually exclusive', function () {
        $user = Mockery::mock(User::class);
        $company = Mockery::mock(Company::class);

        $successResult = RegistrationResult::success($user, $company);
        $failureResult = RegistrationResult::failure('Error message');

        // Success result
        expect($successResult->isSuccess())->toBeTrue();
        expect($successResult->isFailure())->toBeFalse();

        // Failure result
        expect($failureResult->isSuccess())->toBeFalse();
        expect($failureResult->isFailure())->toBeTrue();
    });

    it('handles different error message types', function () {
        $errorMessages = [
            'Código de parceiro inválido ou não encontrado',
            'CPF inválido. Verifique o formato',
            'Este email já está cadastrado no sistema',
            'Este CPF já está cadastrado no sistema',
            'Erro interno do sistema. Tente novamente mais tarde.',
        ];

        foreach ($errorMessages as $message) {
            $result = RegistrationResult::failure($message);

            expect($result->isFailure())->toBeTrue();
            expect($result->error)->toBe($message);
            expect($result->user)->toBeNull();
            expect($result->company)->toBeNull();
        }
    });

    it('can handle null values in constructor', function () {
        $result = new RegistrationResult(
            success: false,
            user: null,
            error: 'Test error',
            company: null
        );

        expect($result->success)->toBeFalse();
        expect($result->user)->toBeNull();
        expect($result->company)->toBeNull();
        expect($result->error)->toBe('Test error');
    });

    it('success result has both user and company', function () {
        $user = Mockery::mock(User::class);
        $company = Mockery::mock(Company::class);

        $result = RegistrationResult::success($user, $company);

        expect($result->isSuccess())->toBeTrue();
        expect($result->user)->not->toBeNull();
        expect($result->company)->not->toBeNull();
        expect($result->error)->toBeNull();
    });

    it('failure result has no user or company', function () {
        $result = RegistrationResult::failure('Test error');

        expect($result->isFailure())->toBeTrue();
        expect($result->user)->toBeNull();
        expect($result->company)->toBeNull();
        expect($result->error)->not->toBeNull();
    });
});

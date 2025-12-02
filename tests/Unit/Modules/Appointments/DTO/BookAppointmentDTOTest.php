<?php

use Carbon\Carbon;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;

describe('BookAppointmentDTO', function () {
    it('can be created with all required properties', function () {
        $userId = 123;
        $categoryType = AppointmentCategoryEnum::PersonalFinance;
        $appointmentAt = Carbon::now();
        $notes = 'Test appointment notes';

        $dto = new BookAppointmentDTO(
            userId: $userId,
            categoryType: $categoryType,
            appointmentAt: $appointmentAt,
            notes: $notes
        );

        expect($dto->userId)->toBe($userId);
        expect($dto->categoryType)->toBe($categoryType);
        expect($dto->appointmentAt)->toBe($appointmentAt);
        expect($dto->notes)->toBe($notes);
    });

    it('can be created without notes', function () {
        $userId = 123;
        $categoryType = AppointmentCategoryEnum::InvestmentAdvisory;
        $appointmentAt = Carbon::now();

        $dto = new BookAppointmentDTO(
            userId: $userId,
            categoryType: $categoryType,
            appointmentAt: $appointmentAt
        );

        expect($dto->userId)->toBe($userId);
        expect($dto->categoryType)->toBe($categoryType);
        expect($dto->appointmentAt)->toBe($appointmentAt);
        expect($dto->notes)->toBeNull();
    });

    it('can be created with string user id', function () {
        $userId = '123';
        $categoryType = AppointmentCategoryEnum::PersonalFinance;
        $appointmentAt = Carbon::now();

        $dto = new BookAppointmentDTO(
            userId: $userId,
            categoryType: $categoryType,
            appointmentAt: $appointmentAt
        );

        expect($dto->userId)->toBe($userId);
    });

    it('can be created using make factory method', function () {
        $userId = 456;
        $payload = [
            'category_type' => 'personal_finance',
            'appointment_at' => '2024-06-15 14:30:00',
            'notes' => 'Factory method test notes',
        ];

        $dto = BookAppointmentDTO::make($userId, $payload);

        expect($dto->userId)->toBe($userId);
        expect($dto->categoryType)->toBe(AppointmentCategoryEnum::PersonalFinance);
        expect($dto->appointmentAt)->toBeInstanceOf(Carbon::class);
        expect($dto->notes)->toBe($payload['notes']);
    });

    it('make method parses date string correctly', function () {
        $userId = 789;
        $dateString = '2024-06-15 14:30:00';
        $payload = [
            'category_type' => 'investment_advisory',
            'appointment_at' => $dateString,
        ];

        $dto = BookAppointmentDTO::make($userId, $payload);

        expect($dto->appointmentAt->format('Y-m-d H:i:s'))->toBe($dateString);
    });

    it('make method handles missing notes', function () {
        $userId = 101;
        $payload = [
            'category_type' => 'personal_finance',
            'appointment_at' => '2024-06-15 14:30:00',
            // notes is missing
        ];

        $dto = BookAppointmentDTO::make($userId, $payload);

        expect($dto->notes)->toBeNull();
    });

    it('make method handles empty notes', function () {
        $userId = 102;
        $payload = [
            'category_type' => 'personal_finance',
            'appointment_at' => '2024-06-15 14:30:00',
            'notes' => null,
        ];

        $dto = BookAppointmentDTO::make($userId, $payload);

        expect($dto->notes)->toBeNull();
    });

    it('handles different appointment categories', function () {
        $categories = [
            'personal_finance' => AppointmentCategoryEnum::PersonalFinance,
            'investment_advisory' => AppointmentCategoryEnum::InvestmentAdvisory,
        ];

        foreach ($categories as $categoryValue => $categoryEnum) {
            $payload = [
                'category_type' => $categoryValue,
                'appointment_at' => '2024-06-15 14:30:00',
            ];

            $dto = BookAppointmentDTO::make(123, $payload);

            expect($dto->categoryType)->toBe($categoryEnum);
        }
    });

    it('can be json serialized', function () {
        $userId = 123;
        $categoryType = AppointmentCategoryEnum::PersonalFinance;
        $appointmentAt = Carbon::create(2024, 6, 15, 14, 30, 0);
        $notes = 'Serialization test notes';

        $dto = new BookAppointmentDTO(
            userId: $userId,
            categoryType: $categoryType,
            appointmentAt: $appointmentAt,
            notes: $notes
        );

        $serialized = $dto->jsonSerialize();

        expect($serialized)->toBe([
            'userId' => $userId,
            'category_type' => $categoryType->value,
            'appointment_at' => $appointmentAt->getTimestampMs(),
            'notes' => $notes,
        ]);
    });

    it('json serialization handles null notes', function () {
        $userId = 456;
        $categoryType = AppointmentCategoryEnum::InvestmentAdvisory;
        $appointmentAt = Carbon::create(2024, 6, 15, 14, 30, 0);

        $dto = new BookAppointmentDTO(
            userId: $userId,
            categoryType: $categoryType,
            appointmentAt: $appointmentAt
        );

        $serialized = $dto->jsonSerialize();

        expect($serialized)->toBe([
            'userId' => $userId,
            'category_type' => $categoryType->value,
            'appointment_at' => $appointmentAt->getTimestampMs(),
            'notes' => null,
        ]);
    });

    it('json serialization uses timestamp in milliseconds', function () {
        $appointmentAt = Carbon::create(2024, 6, 15, 14, 30, 0);
        $expectedTimestamp = $appointmentAt->getTimestampMs();

        $dto = new BookAppointmentDTO(
            userId: 123,
            categoryType: AppointmentCategoryEnum::PersonalFinance,
            appointmentAt: $appointmentAt
        );

        $serialized = $dto->jsonSerialize();

        expect($serialized['appointment_at'])->toBe($expectedTimestamp);
    });

    it('properties are readonly', function () {
        $dto = new BookAppointmentDTO(
            userId: 123,
            categoryType: AppointmentCategoryEnum::PersonalFinance,
            appointmentAt: Carbon::now(),
            notes: 'Test notes'
        );

        // Properties should be accessible for reading
        expect($dto->userId)->toBe(123);
        expect($dto->categoryType)->toBe(AppointmentCategoryEnum::PersonalFinance);
        expect($dto->notes)->toBe('Test notes');

        // Properties should be readonly (attempting to modify would cause an error)
        // $dto->userId = 456; // This would fail
        // $dto->notes = 'Modified notes'; // This would fail
    });

    it('handles different date formats in make method', function () {
        $userId = 123;
        $dateFormats = [
            '2024-06-15 14:30:00',
            '2024-06-15T14:30:00Z',
            '2024-06-15',
        ];

        foreach ($dateFormats as $dateFormat) {
            $payload = [
                'category_type' => 'personal_finance',
                'appointment_at' => $dateFormat,
            ];

            $dto = BookAppointmentDTO::make($userId, $payload);

            expect($dto->appointmentAt)->toBeInstanceOf(Carbon::class);
        }
    });

    it('make and jsonSerialize are compatible operations', function () {
        $userId = 123;
        $originalPayload = [
            'category_type' => 'personal_finance',
            'appointment_at' => '2024-06-15 14:30:00',
            'notes' => 'Round trip test',
        ];

        $dto = BookAppointmentDTO::make($userId, $originalPayload);
        $serialized = $dto->jsonSerialize();

        expect($serialized['userId'])->toBe($userId);
        expect($serialized['category_type'])->toBe($originalPayload['category_type']);
        expect($serialized['notes'])->toBe($originalPayload['notes']);
        expect($serialized['appointment_at'])->toBeInt();
    });
});

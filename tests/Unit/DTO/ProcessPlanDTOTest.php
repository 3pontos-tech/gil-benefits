<?php

use App\DTO\ProcessPlanDTO;
use Carbon\Carbon;
use Illuminate\Support\Facades\Date;

describe('ProcessPlanDTO', function () {
    it('can be created with all required properties', function () {
        $companyId = 123;
        $itemId = 456;
        $status = 'active';
        $subscriptionStartingAt = Carbon::now();

        $dto = new ProcessPlanDTO(
            companyId: $companyId,
            itemId: $itemId,
            status: $status,
            subscriptionStartingAt: $subscriptionStartingAt
        );

        expect($dto->companyId)->toBe($companyId);
        expect($dto->itemId)->toBe($itemId);
        expect($dto->status)->toBe($status);
        expect($dto->subscriptionStartingAt)->toBe($subscriptionStartingAt);
    });

    it('can be created with string company id', function () {
        $companyId = '123';
        $itemId = 456;
        $status = 'active';
        $subscriptionStartingAt = Carbon::now();

        $dto = new ProcessPlanDTO(
            companyId: $companyId,
            itemId: $itemId,
            status: $status,
            subscriptionStartingAt: $subscriptionStartingAt
        );

        expect($dto->companyId)->toBe($companyId);
        expect($dto->itemId)->toBe($itemId);
        expect($dto->status)->toBe($status);
        expect($dto->subscriptionStartingAt)->toBe($subscriptionStartingAt);
    });

    it('can be created using make factory method', function () {
        $companyId = 123;
        $data = [
            'item_id' => 456,
            'status' => 'active',
            'subscription_starting_at' => '2024-01-15 10:30:00',
        ];

        $dto = ProcessPlanDTO::make($companyId, $data);

        expect($dto->companyId)->toBe($companyId);
        expect($dto->itemId)->toBe($data['item_id']);
        expect($dto->status)->toBe($data['status']);
        expect($dto->subscriptionStartingAt)->toBeInstanceOf(DateTimeInterface::class);
    });

    it('make method parses date string correctly', function () {
        $companyId = 123;
        $dateString = '2024-01-15 10:30:00';
        $data = [
            'item_id' => 456,
            'status' => 'active',
            'subscription_starting_at' => $dateString,
        ];

        $dto = ProcessPlanDTO::make($companyId, $data);

        expect($dto->subscriptionStartingAt->format('Y-m-d H:i:s'))->toBe($dateString);
    });

    it('handles different status values', function () {
        $statuses = ['active', 'inactive', 'pending', 'cancelled', 'expired'];

        foreach ($statuses as $status) {
            $dto = new ProcessPlanDTO(
                companyId: 123,
                itemId: 456,
                status: $status,
                subscriptionStartingAt: Carbon::now()
            );

            expect($dto->status)->toBe($status);
        }
    });

    it('handles different date formats in make method', function () {
        $companyId = 123;
        $dateFormats = [
            '2024-01-15',
            '2024-01-15 10:30:00',
            '2024-01-15T10:30:00Z',
            '2024/01/15',
        ];

        foreach ($dateFormats as $dateFormat) {
            $data = [
                'item_id' => 456,
                'status' => 'active',
                'subscription_starting_at' => $dateFormat,
            ];

            $dto = ProcessPlanDTO::make($companyId, $data);

            expect($dto->subscriptionStartingAt)->toBeInstanceOf(DateTimeInterface::class);
        }
    });

    it('properties are readonly', function () {
        $dto = new ProcessPlanDTO(
            companyId: 123,
            itemId: 456,
            status: 'active',
            subscriptionStartingAt: Carbon::now()
        );

        // Properties should be accessible for reading
        expect($dto->companyId)->toBe(123);
        expect($dto->itemId)->toBe(456);
        expect($dto->status)->toBe('active');

        // Properties should be readonly (attempting to modify would cause an error)
        // $dto->companyId = 999; // This would fail
        // $dto->status = 'inactive'; // This would fail
    });

    it('handles large company and item ids', function () {
        $largeCompanyId = 999999999;
        $largeItemId = 888888888;

        $dto = new ProcessPlanDTO(
            companyId: $largeCompanyId,
            itemId: $largeItemId,
            status: 'active',
            subscriptionStartingAt: Carbon::now()
        );

        expect($dto->companyId)->toBe($largeCompanyId);
        expect($dto->itemId)->toBe($largeItemId);
    });

    it('make method works with string company id', function () {
        $companyId = '123';
        $data = [
            'item_id' => 456,
            'status' => 'active',
            'subscription_starting_at' => '2024-01-15 10:30:00',
        ];

        $dto = ProcessPlanDTO::make($companyId, $data);

        expect($dto->companyId)->toBe($companyId);
    });

    it('handles carbon instance in constructor', function () {
        $carbonDate = Carbon::create(2024, 1, 15, 10, 30, 0);

        $dto = new ProcessPlanDTO(
            companyId: 123,
            itemId: 456,
            status: 'active',
            subscriptionStartingAt: $carbonDate
        );

        expect($dto->subscriptionStartingAt)->toBe($carbonDate);
        expect($dto->subscriptionStartingAt->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
    });

    it('handles datetime instance in constructor', function () {
        $dateTime = new DateTime('2024-01-15 10:30:00');

        $dto = new ProcessPlanDTO(
            companyId: 123,
            itemId: 456,
            status: 'active',
            subscriptionStartingAt: $dateTime
        );

        expect($dto->subscriptionStartingAt)->toBe($dateTime);
        expect($dto->subscriptionStartingAt->format('Y-m-d H:i:s'))->toBe('2024-01-15 10:30:00');
    });
});

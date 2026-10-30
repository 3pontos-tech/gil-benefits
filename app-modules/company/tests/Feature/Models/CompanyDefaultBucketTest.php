<?php

declare(strict_types=1);

use TresPontosTech\Company\Models\Company;

it('recognises the default bucket by its slug', function (): void {
    $bucket = Company::factory()->create(['slug' => Company::DEFAULT_SLUG]);
    $client = Company::factory()->create();

    expect($bucket->isDefaultBucket())->toBeTrue()
        ->and($bucket->subsidizesEmployees())->toBeFalse()
        ->and($client->isDefaultBucket())->toBeFalse()
        ->and($client->subsidizesEmployees())->toBeTrue();
});

it('resolves the bucket id, or null when it does not exist', function (): void {
    expect(Company::defaultId())->toBeNull();

    $bucket = Company::factory()->create(['slug' => Company::DEFAULT_SLUG]);

    expect(Company::defaultId())->toBe($bucket->getKey())
        ->and(Company::default()->is($bucket))->toBeTrue();
});

it('leaves the bucket out of withoutDefault', function (): void {
    $bucket = Company::factory()->create(['slug' => Company::DEFAULT_SLUG]);
    $client = Company::factory()->create();

    expect(Company::query()->withoutDefault()->pluck('id')->all())->toBe([$client->getKey()])
        ->and(Company::query()->pluck('id')->sort()->values()->all())->toBe(collect([$bucket->getKey(), $client->getKey()])->sort()->values()->all());
});

<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('r2');
        Http::preventStrayRequests();
    }
}

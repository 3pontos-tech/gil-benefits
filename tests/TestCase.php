<?php

namespace Tests;

use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('r2');
    }
}

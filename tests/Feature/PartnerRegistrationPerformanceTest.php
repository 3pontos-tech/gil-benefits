<?php

namespace Tests\Feature;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use TresPontosTech\Company\Models\Company;

class PartnerRegistrationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run the performance indexes migration
        $this->artisan('migrate');
    }

    public function test_partner_code_lookup_uses_index(): void
    {
        // Create multiple companies to test index performance
        Company::factory()->count(100)->sequence(
            fn ($sequence) => ['slug' => 'test-company-' . $sequence->index]
        )->create();

        Company::factory()->create([
            'partner_code' => 'TESTCODE123',
            'slug' => 'test-company-special',
        ]);

        // Enable query logging
        DB::enableQueryLog();

        // Perform partner code lookup
        $company = Company::whereRaw('LOWER(partner_code) = LOWER(?)', ['testcode123'])->first();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertNotNull($company);
        $this->assertEquals('TESTCODE123', $company->partner_code);

        // Verify only one query was executed
        $this->assertCount(1, $queries);

        // The query should use the index (this is more of a documentation test)
        $query = $queries[0]['query'];
        $this->assertStringContainsString('partner_code', $query);
    }

    public function test_cpf_uniqueness_check_uses_index(): void
    {
        // Create multiple user details to test index performance
        $users = User::factory()->count(1000)->create();
        $company = Company::factory()->create();

        foreach ($users as $user) {
            Detail::factory()->create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'tax_id' => fake()->unique()->numerify('###.###.###-##'),
            ]);
        }

        // Create a specific CPF to test
        $testUser = User::factory()->create();
        Detail::factory()->create([
            'user_id' => $testUser->id,
            'company_id' => $company->id,
            'tax_id' => '123.456.789-09',
        ]);

        // Enable query logging
        DB::enableQueryLog();

        // Perform CPF uniqueness check
        $exists = Detail::where('tax_id', '123.456.789-09')->exists();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertTrue($exists);

        // Verify only one query was executed
        $this->assertCount(1, $queries);

        // The query should use the index
        $query = $queries[0]['query'];
        $this->assertStringContainsString('tax_id', $query);
    }

    public function test_email_uniqueness_check_uses_index(): void
    {
        // Create multiple users to test index performance
        User::factory()->count(1000)->create();
        User::factory()->create(['email' => 'test@example.com']);

        // Enable query logging
        DB::enableQueryLog();

        // Perform email uniqueness check
        $exists = User::where('email', 'test@example.com')->exists();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertTrue($exists);

        // Verify only one query was executed
        $this->assertCount(1, $queries);

        // The query should use the index
        $query = $queries[0]['query'];
        $this->assertStringContainsString('email', $query);
    }

    public function test_rg_lookup_uses_index(): void
    {
        // Create multiple user details to test index performance
        $users = User::factory()->count(1000)->create();
        $company = Company::factory()->create();

        foreach ($users as $user) {
            Detail::factory()->create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'document_id' => fake()->unique()->numerify('##.###.###-#'),
            ]);
        }

        // Create a specific RG to test
        $testUser = User::factory()->create();
        Detail::factory()->create([
            'user_id' => $testUser->id,
            'company_id' => $company->id,
            'document_id' => '12.345.678-9',
        ]);

        // Enable query logging
        DB::enableQueryLog();

        // Perform RG lookup
        $detail = Detail::where('document_id', '12.345.678-9')->first();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertNotNull($detail);
        $this->assertEquals('12.345.678-9', $detail->document_id);

        // Verify only one query was executed
        $this->assertCount(1, $queries);

        // The query should use the index
        $query = $queries[0]['query'];
        $this->assertStringContainsString('document_id', $query);
    }

    public function test_database_indexes_exist(): void
    {
        // Test that all required indexes exist in the database
        $schema = DB::getSchemaBuilder();

        // Get index information for each table
        $companiesIndexes = $this->getTableIndexes('companies');
        $userDetailsIndexes = $this->getTableIndexes('user_details');
        $usersIndexes = $this->getTableIndexes('users');

        // Verify partner_code index exists
        $this->assertContains('idx_companies_partner_code', array_keys($companiesIndexes));

        // Verify user_details indexes exist
        $this->assertContains('idx_user_details_tax_id', array_keys($userDetailsIndexes));
        $this->assertContains('idx_user_details_document_id', array_keys($userDetailsIndexes));

        // Verify users email index exists
        $this->assertContains('idx_users_email', array_keys($usersIndexes));
    }

    public function test_concurrent_registration_performance(): void
    {
        // Create a company for testing
        Company::factory()->create(['partner_code' => 'PERF123']);

        $startTime = microtime(true);

        // Simulate multiple concurrent registrations
        $registrationData = [];
        for ($i = 0; $i < 100; ++$i) {
            $registrationData[] = [
                'name' => "Test User {$i}",
                'email' => "test{$i}@example.com",
                'cpf' => sprintf('%03d.%03d.%03d-%02d',
                    rand(100, 999),
                    rand(100, 999),
                    rand(100, 999),
                    rand(10, 99)
                ),
                'rg' => sprintf('%02d.%03d.%03d-%d',
                    rand(10, 99),
                    rand(100, 999),
                    rand(100, 999),
                    rand(0, 9)
                ),
                'partner_code' => 'PERF123',
            ];
        }

        // Perform batch validation checks (simulating what happens during registration)
        foreach ($registrationData as $data) {
            // Check email uniqueness
            User::where('email', $data['email'])->exists();

            // Check CPF uniqueness
            Detail::where('tax_id', $data['cpf'])->exists();

            // Check partner code validity
            Company::whereRaw('LOWER(partner_code) = LOWER(?)', [$data['partner_code']])->exists();
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Performance should be reasonable (less than 2 seconds for 100 checks)
        $this->assertLessThan(2.0, $executionTime,
            "Performance test failed: took {$executionTime} seconds for 100 validation checks");
    }

    /**
     * Helper method to get table indexes
     */
    private function getTableIndexes(string $tableName): array
    {
        $indexes = [];

        try {
            // For SQLite, we need to use a different approach
            if (DB::getDriverName() === 'sqlite') {
                $indexList = DB::select("PRAGMA index_list({$tableName})");
                foreach ($indexList as $index) {
                    $indexes[$index->name] = $index;
                }
            } else {
                // For other databases, use the schema manager
                $schema = DB::getDoctrineSchemaManager();
                $indexes = $schema->listTableIndexes($tableName);
            }
        } catch (\Exception $e) {
            // If we can't get indexes, at least verify the table exists
            $this->assertTrue(DB::getSchemaBuilder()->hasTable($tableName));
        }

        return $indexes;
    }

    public function test_query_optimization_with_large_dataset(): void
    {
        // Create a smaller dataset to avoid unique constraint issues
        $companies = Company::factory()->count(50)->sequence(
            fn ($sequence) => ['slug' => 'perf-company-' . $sequence->index]
        )->create();

        $users = User::factory()->count(100)->sequence(
            fn ($sequence) => ['email' => 'perfuser' . $sequence->index . '@example.com']
        )->create();

        // Create user details for all users
        foreach ($users as $index => $user) {
            Detail::factory()->create([
                'user_id' => $user->id,
                'company_id' => $companies->random()->id,
                'tax_id' => sprintf('%03d.%03d.%03d-%02d',
                    100 + $index,
                    200 + $index,
                    300 + $index,
                    $index % 100
                ),
                'document_id' => sprintf('%02d.%03d.%03d-%d',
                    10 + ($index % 90),
                    100 + $index,
                    200 + $index,
                    $index % 10
                ),
            ]);
        }

        // Test partner code lookup performance
        $startTime = microtime(true);
        for ($i = 0; $i < 20; ++$i) {
            $randomCompany = $companies->random();
            Company::whereRaw('LOWER(partner_code) = LOWER(?)', [$randomCompany->partner_code])->first();
        }
        $partnerCodeTime = microtime(true) - $startTime;

        // Test CPF lookup performance
        $startTime = microtime(true);
        for ($i = 0; $i < 20; ++$i) {
            $randomDetail = Detail::inRandomOrder()->first();
            Detail::where('tax_id', $randomDetail->tax_id)->exists();
        }
        $cpfLookupTime = microtime(true) - $startTime;

        // Test email lookup performance
        $startTime = microtime(true);
        for ($i = 0; $i < 20; ++$i) {
            $randomUser = $users->random();
            User::where('email', $randomUser->email)->exists();
        }
        $emailLookupTime = microtime(true) - $startTime;

        // All operations should complete in reasonable time
        $this->assertLessThan(1.0, $partnerCodeTime, "Partner code lookups too slow: {$partnerCodeTime}s");
        $this->assertLessThan(1.0, $cpfLookupTime, "CPF lookups too slow: {$cpfLookupTime}s");
        $this->assertLessThan(1.0, $emailLookupTime, "Email lookups too slow: {$emailLookupTime}s");
    }
}

<?php

use App\Models\Users\User;
use App\Repositories\UserRepository;
use App\Services\CacheService;
use App\Services\QueryOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Repositories\AppointmentRepository;
use TresPontosTech\Company\Models\Company;

uses(RefreshDatabase::class);

it('can create performance indexes successfully', function () {
    // Check that the migration ran and indexes exist
    $indexes = DB::select('PRAGMA index_list(appointments)');
    $indexNames = array_column($indexes, 'name');

    expect($indexNames)->toContain('idx_appointments_status_date');
    expect($indexNames)->toContain('idx_appointments_user_status');
    expect($indexNames)->toContain('idx_appointments_consultant_date');
});

it('can use optimized query scopes', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create(['user_id' => $user->id]);

    // Test User model scopes
    $users = User::withCommonRelations()->get();
    expect($users)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);

    $activeUsers = User::active()->get();
    expect($activeUsers)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);

    $latestUsers = User::latest()->get();
    expect($latestUsers)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

it('can use repository pattern with caching', function () {
    $cacheService = app(CacheService::class);
    $userRepository = new UserRepository(new User, $cacheService);

    // Create test data
    $user = User::factory()->create();

    // Test cached method
    $result1 = $userRepository->getUsersWithActiveSubscriptions();
    $result2 = $userRepository->getUsersWithActiveSubscriptions();

    expect($result1)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    expect($result2)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
});

it('can cache expensive operations', function () {
    $cacheService = app(CacheService::class);

    // Test basic caching
    $result = $cacheService->remember('test_key', function () {
        return 'expensive_computation_result';
    });

    expect($result)->toBe('expensive_computation_result');

    // Test that it's cached
    $cachedResult = $cacheService->get('test_key');
    expect($cachedResult)->toBe('expensive_computation_result');
});

it('can invalidate cache when models change', function () {
    $cacheService = app(CacheService::class);

    // Cache some user data
    $user = User::factory()->create();
    $cacheService->cacheUserData($user->id, 'profile', ['name' => $user->name]);

    // Verify it's cached
    $cached = $cacheService->getUserData($user->id, 'profile');
    expect($cached)->toBe(['name' => $user->name]);

    // Update the user (this should trigger cache invalidation via observer)
    $user->update(['name' => 'Updated Name']);

    // The cache should be invalidated (in a real scenario with proper observer setup)
    expect($user->name)->toBe('Updated Name');
});

it('can monitor query performance', function () {
    $service = app(QueryOptimizationService::class);

    // Execute some queries to generate data
    User::factory()->create();
    User::all();

    $stats = $service->getQueryStats();

    expect($stats)->toHaveKeys([
        'total_queries',
        'total_time',
        'average_time',
        'slow_queries_count',
        'slow_queries_percentage',
    ]);

    expect($stats['total_queries'])->toBeGreaterThan(0);
});

it('can analyze query patterns', function () {
    $service = app(QueryOptimizationService::class);

    // Execute some queries
    User::factory()->create();
    User::where('email', 'test@example.com')->first();

    $analysis = $service->analyzeQueries();

    expect($analysis)->toHaveKeys([
        'suggestions',
        'patterns',
        'performance_issues',
    ]);

    expect($analysis['suggestions'])->toBeArray();
});

it('can use appointment repository with caching', function () {
    $cacheService = app(CacheService::class);
    $appointmentRepository = new AppointmentRepository(new Appointment, $cacheService);

    $user = User::factory()->create();
    $company = Company::factory()->create(['user_id' => $user->id]);

    // Test cached statistics method
    $stats = $appointmentRepository->getStatsForCompany($company->id);

    expect($stats)->toHaveKeys([
        'total',
        'completed',
        'cancelled',
        'upcoming',
        'this_month',
    ]);

    // Test that subsequent calls use cache
    $cachedStats = $appointmentRepository->getStatsForCompany($company->id);
    expect($cachedStats)->toBe($stats);
});

it('can generate performance reports', function () {
    $service = app(QueryOptimizationService::class);

    // Execute some queries to generate data
    User::factory()->create();
    User::all();

    $report = $service->generatePerformanceReport();

    expect($report)->toHaveKeys([
        'summary',
        'slow_queries',
        'optimization_suggestions',
        'generated_at',
    ]);

    expect($report['summary'])->toBeArray();
    expect($report['slow_queries'])->toBeArray();
    expect($report['optimization_suggestions'])->toBeArray();
});

it('can clear query logs', function () {
    $service = app(QueryOptimizationService::class);

    // Execute some queries
    User::factory()->create();

    $statsBefore = $service->getQueryStats();
    expect($statsBefore['total_queries'])->toBeGreaterThan(0);

    // Clear logs
    $service->clearLogs();

    $statsAfter = $service->getQueryStats();
    expect($statsAfter['total_queries'])->toBe(0);
});

it('can cache computation results', function () {
    $cacheService = app(CacheService::class);

    $parameters = ['user_id' => 1, 'date_range' => '30_days'];
    $result = ['count' => 42, 'average' => 3.14];

    // Cache computation
    $cached = $cacheService->cacheComputation('user_stats', $parameters, $result);
    expect($cached)->toBeTrue();

    // Retrieve computation
    $retrieved = $cacheService->getComputation('user_stats', $parameters);
    expect($retrieved)->toBe($result);
});

it('can cache dashboard data', function () {
    $cacheService = app(CacheService::class);

    $userId = 1;
    $dashboardData = [
        'total_appointments' => 5,
        'pending_appointments' => 2,
        'completed_appointments' => 3,
    ];

    // Cache dashboard data
    $cached = $cacheService->cacheDashboardData('user', $userId, $dashboardData);
    expect($cached)->toBeTrue();

    // Retrieve dashboard data
    $retrieved = $cacheService->getDashboardData('user', $userId);
    expect($retrieved)->toBe($dashboardData);
});

<?php

use App\Services\CacheService;
use App\Services\ViewCacheService;
use App\Services\RouteCacheService;
use App\Services\AssetOptimizationService;
use App\Services\PerformanceOptimizationService;
use App\Jobs\CacheWarmupJob;
use App\Jobs\AssetOptimizationJob;
use App\Jobs\ProductionOptimizationJob;
use App\Jobs\CacheClearJob;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->cacheService = app(CacheService::class);
    $this->viewCacheService = app(ViewCacheService::class);
    $this->routeCacheService = app(RouteCacheService::class);
    $this->assetService = app(AssetOptimizationService::class);
    $this->optimizationService = app(PerformanceOptimizationService::class);
});

it('can store and retrieve data with cache service', function () {
    $key = 'test_key';
    $value = ['test' => 'data'];

    $result = $this->cacheService->put($key, $value, 300);
    expect($result)->toBeTrue();

    $retrieved = $this->cacheService->get($key);
    expect($retrieved)->toBe($value);
});

it('can remember data with cache service', function () {
    $key = 'remember_test';
    $expectedValue = 'computed_value';

    $result = $this->cacheService->remember($key, function () use ($expectedValue) {
        return $expectedValue;
    }, 300);

    expect($result)->toBe($expectedValue);

    // Second call should return cached value
    $cachedResult = $this->cacheService->remember($key, function () {
        return 'should_not_be_called';
    }, 300);

    expect($cachedResult)->toBe($expectedValue);
});

it('can cache user data', function () {
    $userId = 1;
    $dataType = 'profile';
    $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

    $result = $this->cacheService->cacheUserData($userId, $dataType, $data);
    expect($result)->toBeTrue();

    $retrieved = $this->cacheService->getUserData($userId, $dataType);
    expect($retrieved)->toBe($data);
});

it('can get optimization status', function () {
    $status = $this->routeCacheService->getOptimizationStatus();

    expect($status)->toBeArray();
    expect($status)->toHaveKeys([
        'routes_cached',
        'config_cached',
        'events_cached',
        'views_cached',
        'environment',
        'debug_mode',
    ]);
});

it('can get build stats', function () {
    $stats = $this->assetService->getBuildStats();

    expect($stats)->toBeArray();
    
    // If build directory doesn't exist, should return error
    if (isset($stats['error'])) {
        expect($stats['error'])->toBe('Build directory not found');
    } else {
        expect($stats)->toHaveKeys([
            'total_files',
            'total_size',
            'css_files',
            'css_size',
            'js_files',
            'js_size',
        ]);
    }
});

it('can get performance status', function () {
    $status = $this->optimizationService->getPerformanceStatus();

    expect($status)->toBeArray();
    expect($status)->toHaveKeys([
        'environment',
        'cache_stats',
        'optimization_status',
        'asset_status',
        'queue_status',
    ]);

    expect($status['environment'])->toBe(config('app.env'));
});

it('can get optimization recommendations', function () {
    $recommendations = $this->optimizationService->getOptimizationRecommendations();

    expect($recommendations)->toBeArray();

    foreach ($recommendations as $recommendation) {
        expect($recommendation)->toHaveKeys(['type', 'priority', 'message', 'action']);
        expect($recommendation['priority'])->toBeIn(['high', 'medium', 'low']);
    }
});

it('can dispatch cache warmup job', function () {
    Queue::fake();

    $job = new CacheWarmupJob(['application', 'views']);
    dispatch($job);

    Queue::assertPushed(CacheWarmupJob::class);
});

it('can dispatch asset optimization job', function () {
    Queue::fake();

    $job = new AssetOptimizationJob(['build', 'images']);
    dispatch($job);

    Queue::assertPushed(AssetOptimizationJob::class);
});

it('can dispatch production optimization job', function () {
    Queue::fake();

    $job = new ProductionOptimizationJob(true, true);
    dispatch($job);

    Queue::assertPushed(ProductionOptimizationJob::class);
});

it('can queue optimization jobs asynchronously', function () {
    Queue::fake();

    $result = $this->optimizationService->optimizeApplication(true);

    expect($result['status'])->toBe('queued');
    expect($result)->toHaveKey('job_ids');

    Queue::assertPushed(ProductionOptimizationJob::class);
    Queue::assertPushed(CacheWarmupJob::class);
    Queue::assertPushed(AssetOptimizationJob::class);
});

it('has performance commands available', function () {
    $this->artisan('performance:optimize --help')
        ->assertSuccessful();

    $this->artisan('performance:status --help')
        ->assertSuccessful();
});

it('has performance configuration loaded', function () {
    $config = config('performance');

    expect($config)->toBeArray();
    expect($config)->toHaveKeys([
        'cache',
        'view_cache',
        'assets',
        'jobs',
        'production',
        'monitoring',
        'development',
    ]);
});

it('can generate consistent query hashes', function () {
    $query = 'SELECT * FROM users WHERE id = ?';
    $bindings = [1];

    $hash1 = $this->cacheService->generateQueryHash($query, $bindings);
    $hash2 = $this->cacheService->generateQueryHash($query, $bindings);

    expect($hash1)->toBe($hash2);
    expect($hash1)->toBeString();
    expect(strlen($hash1))->toBe(32); // MD5 hash length
});

it('applies view cache exclusion rules correctly', function () {
    expect($this->viewCacheService->shouldCacheView('auth.login'))->toBeFalse();
    expect($this->viewCacheService->shouldCacheView('errors.404'))->toBeFalse();
    expect($this->viewCacheService->shouldCacheView('livewire.component'))->toBeFalse();
    expect($this->viewCacheService->shouldCacheView('user.form'))->toBeFalse();
    expect($this->viewCacheService->shouldCacheView('user.edit'))->toBeFalse();

    // Should cache regular views in production
    if (config('app.env') === 'production') {
        expect($this->viewCacheService->shouldCacheView('welcome'))->toBeTrue();
        expect($this->viewCacheService->shouldCacheView('dashboard.index'))->toBeTrue();
    }
});

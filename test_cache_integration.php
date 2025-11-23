<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\CacheManager;

echo "=== CACHE MANAGER INTEGRATION TEST ===\n\n";

// Test 1: Counter Methods
echo "1. Testing Counter Methods:\n";
CacheManager::resetCounter('test_inbound_count');
echo '   - Initial counter: '.CacheManager::getCounter('test_inbound_count')."\n";
$newCount = CacheManager::incrementCounter('test_inbound_count', 5);
echo "   - After increment by 5: {$newCount}\n";
$newCount = CacheManager::decrementCounter('test_inbound_count', 2);
echo "   - After decrement by 2: {$newCount}\n";

// Test 2: Get All Counters
echo "\n2. Testing Get All Counters:\n";
CacheManager::incrementCounter('inbound_pending', 10);
CacheManager::incrementCounter('outbound_approved', 20);
$counters = CacheManager::getAllCounters();
echo '   - Total counters found: '.count($counters)."\n";
foreach ($counters as $key => $value) {
    echo "     * {$key}: {$value}\n";
}

// Test 3: Warehouse Cache
echo "\n3. Testing Warehouse Cache:\n";
$testKey = 'wh:gudang_a:stats';
CacheManager::remember($testKey, 3600, fn () => ['total' => 100, 'pending' => 5], 'warehouse-a');
echo "   - Cached warehouse data for gudang_a\n";

// Test 4: Invalidate Warehouse
echo "\n4. Testing Warehouse Invalidation:\n";
$cleared = CacheManager::invalidateWarehouse('gudang_a');
echo "   - Keys cleared: {$cleared}\n";

// Test 5: Forget Method
echo "\n5. Testing Forget Method:\n";
$testForgetKey = 'test_forget_key';
Cache::put($testForgetKey, 'test_value', 60);
echo '   - Before forget: '.(Cache::has($testForgetKey) ? 'EXISTS' : 'NOT FOUND')."\n";
CacheManager::forget($testForgetKey);
echo '   - After forget: '.(Cache::has($testForgetKey) ? 'EXISTS' : 'NOT FOUND')."\n";

// Test 6: Cache Statistics
echo "\n6. Testing Cache Statistics:\n";
$stats = CacheManager::getStatistics();
if (isset($stats['keys'])) {
    echo '   - Total keys: '.count($stats['keys'])."\n";
    echo '   - Sample keys: '.implode(', ', array_slice($stats['keys'], 0, 5))."\n";
} else {
    echo '   - Statistics: '.json_encode($stats)."\n";
}

// Cleanup
echo "\n7. Cleanup:\n";
CacheManager::resetCounter('test_inbound_count');
CacheManager::resetCounter('inbound_pending');
CacheManager::resetCounter('outbound_approved');
echo "   - All test counters reset\n";

echo "\n=== ALL TESTS COMPLETED ===\n";

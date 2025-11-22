<?php

namespace App\Http\Controllers;

use App\Services\RedisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Example Controller - Redis Usage Examples
 *
 * Contoh penggunaan RedisService dalam aplikasi Warehouse Management
 */
class ExampleRedisController extends Controller
{
    /**
     * Example 1: Cache warehouse statistics
     */
    public function getStatistics()
    {
        // Cache for 5 minutes (300 seconds)
        $stats = RedisService::rememberWarehouseData('statistics', 300, function () {
            return [
                'total_products' => DB::table('products')->count(),
                'total_orders' => DB::table('orders')->count(),
                'total_revenue' => DB::table('orders')->sum('total'),
                'low_stock_items' => DB::table('products')->where('stock', '<', 10)->count(),
            ];
        });

        return response()->json($stats);
    }

    /**
     * Example 2: Cache product list with pagination
     */
    public function getProducts(Request $request)
    {
        $page = $request->get('page', 1);
        $cacheKey = "products:page:{$page}";

        $products = RedisService::rememberWarehouseData($cacheKey, 600, function () use ($page) {
            return DB::table('products')
                ->paginate(20, ['*'], 'page', $page);
        });

        return response()->json($products);
    }

    /**
     * Example 3: Increment/Decrement counters
     */
    public function trackPageView()
    {
        // Increment page view counter
        $views = RedisService::incrementCounter('page_views');

        return response()->json([
            'total_views' => $views,
        ]);
    }

    /**
     * Example 4: Temporary data with expiration
     */
    public function setTemporaryData(Request $request)
    {
        $key = $request->input('key');
        $value = $request->input('value');
        $ttl = $request->input('ttl', 3600); // Default 1 hour

        RedisService::setex($key, $value, $ttl);

        return response()->json([
            'message' => 'Data cached successfully',
            'expires_in' => $ttl.' seconds',
        ]);
    }

    /**
     * Example 5: Cache multiple products at once
     */
    public function cacheMultipleProducts(array $products)
    {
        $data = [];
        foreach ($products as $product) {
            $data["product:{$product['id']}"] = $product;
        }

        RedisService::setMultiple($data, 3600);

        return response()->json([
            'message' => count($products).' products cached',
        ]);
    }

    /**
     * Example 6: Get multiple products at once
     */
    public function getMultipleProducts(array $productIds)
    {
        $keys = array_map(fn ($id) => "product:{$id}", $productIds);
        $products = RedisService::getMultiple($keys);

        return response()->json($products);
    }

    /**
     * Example 7: Clear specific warehouse cache
     */
    public function clearProductCache()
    {
        // Clear specific cache
        RedisService::forgetWarehouseData('products');

        // Or clear all warehouse cache
        RedisService::flushWarehouseCache();

        return response()->json([
            'message' => 'Cache cleared successfully',
        ]);
    }

    /**
     * Example 8: Check Redis health
     */
    public function checkHealth()
    {
        $isHealthy = RedisService::isHealthy();

        if ($isHealthy) {
            $info = RedisService::getConnectionInfo('cache');

            return response()->json([
                'status' => 'healthy',
                'redis_info' => $info,
            ]);
        }

        return response()->json([
            'status' => 'unhealthy',
            'message' => 'Redis connection failed',
        ], 503);
    }

    /**
     * Example 9: Cache search results
     */
    public function searchProducts(Request $request)
    {
        $query = $request->get('q');
        $cacheKey = 'search:'.md5($query);

        $results = RedisService::rememberWarehouseData($cacheKey, 1800, function () use ($query) {
            return DB::table('products')
                ->where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->get();
        });

        return response()->json($results);
    }

    /**
     * Example 10: Rate limiting example
     */
    public function apiEndpoint(Request $request)
    {
        $userId = $request->user()?->id ?? $request->ip();
        $counterKey = "rate_limit:{$userId}:".now()->format('YmdH');

        $attempts = RedisService::incrementCounter($counterKey);

        if ($attempts > 100) { // Max 100 requests per hour
            return response()->json([
                'error' => 'Rate limit exceeded',
            ], 429);
        }

        // Your API logic here
        return response()->json([
            'data' => 'API response',
            'rate_limit_remaining' => 100 - $attempts,
        ]);
    }

    /**
     * Example 11: Cache user preferences
     */
    public function getUserPreferences(int $userId)
    {
        $cacheKey = "user_preferences:{$userId}";

        $preferences = RedisService::rememberWarehouseData($cacheKey, 86400, function () use ($userId) {
            return DB::table('user_preferences')
                ->where('user_id', $userId)
                ->first();
        });

        return response()->json($preferences);
    }

    /**
     * Example 12: Cache top selling products
     */
    public function getTopSellingProducts()
    {
        $products = RedisService::rememberWarehouseData('top_selling_products', 3600, function () {
            return DB::table('products')
                ->join('order_items', 'products.id', '=', 'order_items.product_id')
                ->select('products.*', DB::raw('SUM(order_items.quantity) as total_sold'))
                ->groupBy('products.id')
                ->orderBy('total_sold', 'DESC')
                ->limit(10)
                ->get();
        });

        return response()->json($products);
    }

    /**
     * Example 13: Inventory update with cache invalidation
     */
    public function updateInventory(Request $request, int $productId)
    {
        // Update database
        DB::table('products')
            ->where('id', $productId)
            ->update(['stock' => $request->input('stock')]);

        // Invalidate cache
        RedisService::forgetWarehouseData("product:{$productId}");
        RedisService::forgetWarehouseData('products');
        RedisService::forgetWarehouseData('statistics');

        return response()->json([
            'message' => 'Inventory updated and cache invalidated',
        ]);
    }

    /**
     * Example 14: Session alternative with Redis
     */
    public function setUserSession(Request $request)
    {
        $userId = $request->user()->id;
        $sessionData = [
            'last_activity' => now(),
            'cart_items' => $request->input('cart_items', []),
            'preferences' => $request->input('preferences', []),
        ];

        RedisService::setex("user_session:{$userId}", $sessionData, 7200); // 2 hours

        return response()->json([
            'message' => 'Session data saved',
        ]);
    }

    /**
     * Example 15: Get all cached keys for debugging
     */
    public function debugCacheKeys()
    {
        if (config('app.debug')) {
            $keys = RedisService::getAllKeys();
            $dbSize = RedisService::getDatabaseSize('cache');

            return response()->json([
                'total_keys' => count($keys),
                'database_size' => $dbSize,
                'keys' => $keys,
            ]);
        }

        return response()->json(['error' => 'Debug mode disabled'], 403);
    }
}

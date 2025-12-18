<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TableSession;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Stats Overview
        $today = Carbon::today();
        
        // Today's Revenue (Paid orders)
        $todaysRevenue = Order::whereDate('created_at', $today)
            ->where('payment_status', 'paid')
            ->sum('total') / 100;

        $yesterdaysRevenue = Order::whereDate('created_at', Carbon::yesterday())
            ->where('payment_status', 'paid')
            ->sum('total') / 100;

        $revenueGrowth = $yesterdaysRevenue > 0 
            ? (($todaysRevenue - $yesterdaysRevenue) / $yesterdaysRevenue) * 100 
            : 100;

        // Orders Today
        $ordersToday = Order::whereDate('created_at', $today)->count();
        $ordersYesterday = Order::whereDate('created_at', Carbon::yesterday())->count();
        $ordersGrowth = $ordersYesterday > 0
            ? (($ordersToday - $ordersYesterday) / $ordersYesterday) * 100
            : 100;

        // Active Devices (Active Sessions)
        $activeDevices = TableSession::where('status', 'active')->count();
        $activeDevicesYesterday = TableSession::whereDate('created_at', Carbon::yesterday())
            ->where('status', 'active') // This comparison is a bit loose but accepted for now
            ->count(); 
        // Better metric: Unique devices seen today vs yesterday, but sessions is okay proxy
        $totalSessionsToday = TableSession::whereDate('created_at', $today)->count();
        $totalSessionsYesterday = TableSession::whereDate('created_at', Carbon::yesterday())->count();
        $devicesGrowth = $totalSessionsYesterday > 0
            ? (($totalSessionsToday - $totalSessionsYesterday) / $totalSessionsYesterday) * 100
            : 0;

        // Avg Rating (Placeholder)
        $avgRating = 4.8;
        $ratingGrowth = 0.3;

        // 2. Sales Chart (Last 30 Days)
        $salesChartData = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total)/100 as total')
            )
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->where('payment_status', 'paid')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 3. Top Products (Parse JSON items) 
        // Note: For high volume, this should be an aggregated table or cached.
        $recentOrders = Order::where('created_at', '>=', $today)->get();
        $productStats = [];

        foreach ($recentOrders as $order) {
            if (is_array($order->items)) {
                foreach ($order->items as $item) {
                     // Assuming item structure has 'id', 'name', 'price', 'quantity'
                    $id = $item['id'] ?? $item['name']; // Fallback key
                    if (!isset($productStats[$id])) {
                        $productStats[$id] = [
                            'name' => $item['name'],
                            'orders' => 0,
                            'revenue' => 0,
                        ];
                    }
                    $productStats[$id]['orders'] += $item['quantity'];
                    $productStats[$id]['revenue'] += ($item['price'] * $item['quantity']);
                }
            }
        }
        
        // Convert product revenue to dollars
        foreach ($productStats as &$stat) {
            $stat['revenue'] = $stat['revenue'] / 100;
        }
        unset($stat);

        // Sort by orders desc
        usort($productStats, function ($a, $b) {
            return $b['orders'] <=> $a['orders'];
        });
        
        $topProducts = array_slice($productStats, 0, 5);

        // 4. Recent Orders
        $latestOrders = Order::with('table')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'device_info' => $order->table ? "Table {$order->table->number}" : "Takeaway", // Simplified
                    'items_count' => count($order->items ?? []),
                    'total' => $order->total / 100,
                    'status' => $order->status,
                    'created_at_human' => $order->created_at->diffForHumans(),
                ];
            });

        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => [
                'revenue' => [
                    'value' => $todaysRevenue,
                    'growth' => round($revenueGrowth, 1),
                ],
                'orders' => [
                    'value' => $ordersToday,
                    'growth' => round($ordersGrowth, 1),
                ],
                'devices' => [
                    'value' => $activeDevices,
                    'growth' => round($devicesGrowth, 1),
                ],
                'rating' => [
                    'value' => $avgRating,
                    'growth' => $ratingGrowth,
                ],
            ],
            'salesChart' => $salesChartData,
            'topProducts' => $topProducts,
            'recentOrders' => $latestOrders,
        ]);
    }

    public function analytics()
    {
        // Default to Last 30 days for better initial data visibility
        $fromDate = Carbon::now()->subDays(30);
        $previousFromDate = Carbon::now()->subDays(60); // Previous 30-day window
        
        // Key Metrics (Current Period)
        $revenue = Order::where('created_at', '>=', $fromDate)
            ->where('payment_status', 'paid')
            ->sum('total') / 100;
            
        $totalOrders = Order::where('created_at', '>=', $fromDate)->count();
        $tableTurnovers = TableSession::where('created_at', '>=', $fromDate)->count();
        $avgOrderValue = $totalOrders > 0 ? $revenue / $totalOrders : 0;

        // Key Metrics (Previous Period) for Growth Calculation
        $prevRevenue = Order::whereBetween('created_at', [$previousFromDate, $fromDate])
            ->where('payment_status', 'paid')
            ->sum('total') / 100;
        $prevOrders = Order::whereBetween('created_at', [$previousFromDate, $fromDate])->count();
        $prevTurnovers = TableSession::whereBetween('created_at', [$previousFromDate, $fromDate])->count();
        $prevAvgOrderValue = $prevOrders > 0 ? $prevRevenue / $prevOrders : 0;

        // Helper for growth calculation
        $calculateGrowth = function($current, $previous) {
            if ($previous > 0) {
                return round((($current - $previous) / $previous) * 100, 1);
            }
            return $current > 0 ? 100 : 0;
        };

        $metrics = [
            'revenue' => [
                'value' => $revenue,
                'growth' => $calculateGrowth($revenue, $prevRevenue),
                'difference' => $revenue - $prevRevenue
            ],
            'orders' => [
                'value' => $totalOrders,
                'growth' => $calculateGrowth($totalOrders, $prevOrders),
                'difference' => $totalOrders - $prevOrders
            ],
            'turnovers' => [
                'value' => $tableTurnovers,
                'growth' => $calculateGrowth($tableTurnovers, $prevTurnovers),
                'difference' => $tableTurnovers - $prevTurnovers
            ],
            'avgOrderValue' => [
                'value' => $avgOrderValue,
                'growth' => $calculateGrowth($avgOrderValue, $prevAvgOrderValue),
                'difference' => $avgOrderValue - $prevAvgOrderValue
            ]
        ];

        // Top Products (Parse JSON items) 
        // Current Period
        $recentOrders = Order::where('created_at', '>=', $fromDate)->get();
        // Previous Period for Growth
        $previousFromDate = $fromDate->copy()->subDays(30);
        $previousOrders = Order::whereBetween('created_at', [$previousFromDate, $fromDate])->get();
        
        $previousProductStats = [];
        // Aggregate Previous Stats first
        foreach ($previousOrders as $order) {
            if (is_array($order->items)) {
                foreach ($order->items as $item) {
                    $id = $item['id'] ?? $item['name'];
                    if (!isset($previousProductStats[$id])) {
                        $previousProductStats[$id] = 0;
                    }
                    $previousProductStats[$id] += $item['quantity'];
                }
            }
        }

        $productStats = [];
        $categoryStats = [];

        // Fetch all menu items with their categories for lookup
        $menuItems = \App\Models\MenuItem::with('category')->get()->keyBy('name');
        
        foreach ($recentOrders as $order) {
            if (is_array($order->items)) {
                foreach ($order->items as $item) {
                     // Assuming item structure has 'id', 'name', 'price', 'quantity'
                    $id = $item['id'] ?? $item['name']; // Fallback key
                    
                    // Try to get category from JSON, otherwise look it up from database
                    $category = $item['category'] ?? 'Uncategorized';
                    if ($category === 'Uncategorized' || !$category) {
                        $menuItem = $menuItems->get($item['name']);
                        if ($menuItem && $menuItem->category) {
                            $category = $menuItem->category->name;
                        }
                    }

                    if (!isset($productStats[$id])) {
                        $productStats[$id] = [
                            'name' => $item['name'],
                            'category' => $category,
                            'orders' => 0,
                            'revenue' => 0,
                            'growth' => 0, 
                            'positive' => true,
                        ];
                    }
                    $productStats[$id]['orders'] += $item['quantity'];
                    $productStats[$id]['revenue'] += ($item['price'] * $item['quantity']);

                    // Category Stats
                    if (!isset($categoryStats[$category])) {
                        $categoryStats[$category] = 0;
                    }
                    $categoryStats[$category] += $item['quantity'];
                }
            }
        }
        
        // Convert product revenue to dollars for analytics
        foreach ($productStats as &$stat) {
            $stat['revenue'] = $stat['revenue'] / 100; 
        }
        unset($stat);
        
        // Calculate Growth
        foreach ($productStats as $id => &$stat) {
             $previousOrdersCount = $previousProductStats[$id] ?? 0;
             $currentOrdersCount = $stat['orders'];
             
             if ($previousOrdersCount > 0) {
                 $growth = (($currentOrdersCount - $previousOrdersCount) / $previousOrdersCount) * 100;
             } else {
                 $growth = $currentOrdersCount > 0 ? 100 : 0;
             }
             
             $stat['growth'] = round($growth, 1);
             $stat['positive'] = $growth >= 0;
        }
        
        // Sort by orders desc
        usort($productStats, function ($a, $b) {
            return $b['orders'] <=> $a['orders'];
        });

        // Add Rank
        foreach ($productStats as $index => &$stat) {
            $stat['id'] = $index + 1; // Fake ID
            $stat['rank'] = $index + 1;
        }
        
        $topProducts = array_slice($productStats, 0, 10);
        
        // Format category data for chart
        $totalItems = array_sum($categoryStats);
        $categoryData = [];
        $colors = ['bg-primary', 'bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-yellow-500'];
        $i = 0;
        foreach ($categoryStats as $cat => $count) {
            $categoryData[] = [
                'category' => $cat,
                'percentage' => $totalItems > 0 ? round(($count / $totalItems) * 100, 1) : 0,
                'color' => $colors[$i % count($colors)]
            ];
            $i++;
        }

        // Peak Hours
        // Group orders by hour (SQLite compatible)
        $ordersByHour = Order::select(DB::raw("strftime('%H', created_at) as hour"), DB::raw('count(*) as count'))
            ->where('created_at', '>=', $fromDate)
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->take(5)
            ->get();
            
        $peakHours = [];
        $maxOrders = $ordersByHour->max('count');
        foreach ($ordersByHour as $record) {
            $hour = (int)$record->hour; // Cast to int since strftime returns string
            $nextHour = $hour + 1;
            $peakHours[] = [
                'time' => sprintf("%d:00 - %d:00", $hour, $nextHour),
                'orders' => $record->count,
                'percentage' => $maxOrders > 0 ? round(($record->count / $maxOrders) * 100) : 0
            ];
        }

        // Sales Chart for Analytics (Daily revenue for period)
        $salesChartData = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total)/100 as total')
            )
            ->where('created_at', '>=', $fromDate)
            ->where('payment_status', 'paid')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return Inertia::render('Admin/Analytics/Index', [
            'keyMetrics' => $metrics,
            'topProducts' => $topProducts,
            'categoryData' => $categoryData,
            'peakHours' => $peakHours,
            'salesChart' => $salesChartData,
        ]);
    }
}

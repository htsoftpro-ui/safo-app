<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalUsers = User::count();
        $totalCustomers = User::where('type', 'customer')->count();
        $totalSuppliers = User::where('type', 'supplier')->count();

        $verifiedSuppliers = Supplier::where('is_verified', true)->count();
        $pendingSuppliers = Supplier::where('is_verified', false)->count();

        $orderCounts = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $totalRevenue = Order::where('status', Order::STATUS_DELIVERED)->sum('total_amount');
        $monthRevenue = Order::where('status', Order::STATUS_DELIVERED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');

        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $lowStockProducts = Product::where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();

        $recentOrders = Order::with(['user', 'supplier'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'customer_name' => $o->user->name ?? 'N/A',
                'supplier_name' => $o->supplier->company_name ?? 'N/A',
                'status' => $o->status,
                'total_amount' => (float) $o->total_amount,
                'created_at' => $o->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'users' => ['total' => $totalUsers, 'customers' => $totalCustomers, 'suppliers' => $totalSuppliers],
                'suppliers' => ['total' => $totalSuppliers, 'verified' => $verifiedSuppliers, 'pending' => $pendingSuppliers],
                'orders' => [
                    'total' => $orderCounts->sum(),
                    'pending' => $orderCounts->get('pending', 0),
                    'confirmed' => $orderCounts->get('confirmed', 0),
                    'processing' => $orderCounts->get('processing', 0),
                    'ready' => $orderCounts->get('ready', 0),
                    'shipped' => $orderCounts->get('shipped', 0),
                    'delivered' => $orderCounts->get('delivered', 0),
                    'cancelled' => $orderCounts->get('cancelled', 0),
                    'returned' => $orderCounts->get('returned', 0),
                ],
                'revenue' => ['total' => (float) $totalRevenue, 'this_month' => (float) $monthRevenue],
                'products' => ['total' => $totalProducts, 'active' => $activeProducts, 'low_stock' => $lowStockProducts],
                'recent_orders' => $recentOrders,
            ],
        ]);
    }
}

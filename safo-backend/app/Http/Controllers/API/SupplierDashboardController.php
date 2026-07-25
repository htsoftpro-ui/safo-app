<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierDashboardController extends Controller
{
    private function getSupplier(Request $request): Supplier
    {
        return Supplier::where('user_id', $request->user()->id)->firstOrFail();
    }

    /**
     * Dashboard stats for the supplier.
     */
    public function index(Request $request)
    {
        $supplier = $this->getSupplier($request);
        $supplierId = $supplier->id;

        // Order counts by status
        $orderCounts = Order::where('supplier_id', $supplierId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Revenue (delivered orders)
        $totalRevenue = Order::where('supplier_id', $supplierId)
            ->where('status', Order::STATUS_DELIVERED)
            ->sum('total_amount');

        // This month's revenue
        $monthRevenue = Order::where('supplier_id', $supplierId)
            ->where('status', Order::STATUS_DELIVERED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');

        // Today's orders
        $todayOrders = Order::where('supplier_id', $supplierId)
            ->whereDate('created_at', today())
            ->count();

        // Total products
        $totalProducts = Product::where('supplier_id', $supplierId)->count();
        $activeProducts = Product::where('supplier_id', $supplierId)->where('is_active', true)->count();

        // Low stock products
        $lowStockProducts = Product::where('supplier_id', $supplierId)
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();

        // Recent orders (last 5)
        $recentOrders = Order::with(['user', 'items'])
            ->where('supplier_id', $supplierId)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->user->name,
                'status' => $order->status,
                'total_amount' => (float) $order->total_amount,
                'items_count' => $order->items->sum('quantity'),
                'created_at' => $order->created_at->toIso8601String(),
            ]);

        // Top selling products
        $topProducts = Product::where('supplier_id', $supplierId)
            ->where('is_active', true)
            ->orderByDesc('sales_count')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => (float) $p->price,
                'sales_count' => $p->sales_count,
                'stock_quantity' => $p->stock_quantity,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => [
                    'total' => $orderCounts->sum(),
                    'pending' => $orderCounts->get(Order::STATUS_PENDING, 0),
                    'confirmed' => $orderCounts->get(Order::STATUS_CONFIRMED, 0),
                    'processing' => $orderCounts->get(Order::STATUS_PROCESSING, 0),
                    'ready' => $orderCounts->get(Order::STATUS_READY, 0),
                    'shipped' => $orderCounts->get(Order::STATUS_SHIPPED, 0),
                    'delivered' => $orderCounts->get(Order::STATUS_DELIVERED, 0),
                    'cancelled' => $orderCounts->get(Order::STATUS_CANCELLED, 0),
                    'today' => $todayOrders,
                ],
                'revenue' => [
                    'total' => (float) $totalRevenue,
                    'this_month' => (float) $monthRevenue,
                ],
                'products' => [
                    'total' => $totalProducts,
                    'active' => $activeProducts,
                    'low_stock' => $lowStockProducts,
                ],
                'recent_orders' => $recentOrders,
                'top_products' => $topProducts,
            ],
        ]);
    }
}

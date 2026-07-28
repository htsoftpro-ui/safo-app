<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function sales(Request $request)
    {
        $request->validate([
            'period' => 'nullable|in:today,week,month,year',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        $period = $request->input('period', 'month');

        $query = Order::where('status', Order::STATUS_DELIVERED);

        match ($period) {
            'today' => $query->whereDate('created_at', today()),
            'week' => $query->where('created_at', '>=', now()->subWeek()),
            'month' => $query->where('created_at', '>=', now()->subMonth()),
            'year' => $query->where('created_at', '>=', now()->subYear()),
        };

        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $totalOrders = $query->count();
        $totalRevenue = (float) $query->sum('total_amount');
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        // Daily breakdown
        $daily = Order::where('status', Order::STATUS_DELIVERED)
            ->when($request->has('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($period === 'today', fn ($q) => $q->whereDate('created_at', today()))
            ->when($period === 'week', fn ($q) => $q->where('created_at', '>=', now()->subWeek()))
            ->when($period === 'month', fn ($q) => $q->where('created_at', '>=', now()->subMonth()))
            ->when($period === 'year', fn ($q) => $q->where('created_at', '>=', now()->subYear()))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'avg_order_value' => $avgOrderValue,
                'daily' => $daily,
            ],
        ]);
    }

    public function suppliers(Request $request)
    {
        $suppliers = Supplier::with('user')
            ->selectRaw('suppliers.*, 
                (SELECT COUNT(*) FROM orders WHERE orders.supplier_id = suppliers.id AND orders.status = "delivered") as delivered_orders,
                (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE orders.supplier_id = suppliers.id AND orders.status = "delivered") as total_revenue,
                (SELECT COUNT(*) FROM products WHERE products.supplier_id = suppliers.id) as product_count')
            ->orderByDesc('total_revenue')
            ->paginate($request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $suppliers]);
    }

    public function users(Request $request)
    {
        $stats = [
            'total' => User::count(),
            'customers' => User::where('type', 'customer')->count(),
            'suppliers' => User::where('type', 'supplier')->count(),
            'admins' => User::where('type', 'admin')->count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'new_today' => User::whereDate('created_at', today())->count(),
            'new_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
            'new_this_month' => User::where('created_at', '>=', now()->subMonth())->count(),
        ];

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function financial(Request $request)
    {
        $totalRevenue = (float) Order::where('status', Order::STATUS_DELIVERED)->sum('total_amount');
        $monthRevenue = (float) Order::where('status', Order::STATUS_DELIVERED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
        $pendingPayments = (float) Order::where('payment_status', 'pending')
            ->whereIn('status', ['confirmed', 'processing', 'ready', 'shipped'])
            ->sum('total_amount');

        $topSuppliers = Supplier::selectRaw('suppliers.id, suppliers.company_name, 
            SUM(orders.total_amount) as revenue, COUNT(orders.id) as order_count')
            ->join('orders', 'suppliers.id', '=', 'orders.supplier_id')
            ->where('orders.status', Order::STATUS_DELIVERED)
            ->groupBy('suppliers.id', 'suppliers.company_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $totalRevenue,
                'month_revenue' => $monthRevenue,
                'pending_payments' => $pendingPayments,
                'top_suppliers' => $topSuppliers,
            ],
        ]);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSupplierAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $supplier = $user?->supplier;

        if (!$user || $user->type !== 'supplier') {
            return response()->json([
                'success' => false,
                'message' => 'هذه المساحة مخصصة للموردين فقط',
            ], 403);
        }

        if (!$supplier || !$supplier->is_verified || !$supplier->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'حساب المورد قيد المراجعة أو غير نشط. ستظهر اللوحة بعد اعتماد الإدارة.',
                'code' => 'SUPPLIER_PENDING',
            ], 403);
        }

        return $next($request);
    }
}

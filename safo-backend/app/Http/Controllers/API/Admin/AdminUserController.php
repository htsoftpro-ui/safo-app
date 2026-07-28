<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'type' => 'nullable|in:customer,supplier,admin',
            'search' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $users = User::query()
            ->when($request->has('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->has('search'), fn ($q) => $q->where(function ($q2) use ($request) {
                $q2->where('name', 'like', "%{$request->search}%")
                   ->orWhere('phone', 'like', "%{$request->search}%")
                   ->orWhere('email', 'like', "%{$request->search}%");
            }))
            ->with('supplier')
            ->latest()
            ->paginate($request->input('per_page', 20));

        return UserResource::collection($users);
    }

    public function show(User $user)
    {
        $user->load('supplier', 'addresses', 'orders');

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    public function toggleStatus(User $user)
    {
        $newStatus = !$user->is_active;
        $user->update(['is_active' => $newStatus]);

        // If disabling a supplier, also deactivate their supplier record and all products
        if ($user->isSupplier() && !$newStatus) {
            if ($user->supplier) {
                $user->supplier->update(['is_active' => false]);
                $user->supplier->products()->update(['is_active' => false]);
            }
            // Revoke all tokens
            $user->tokens()->delete();
        }

        // If enabling a supplier, reactivate supplier record (products stay inactive for review)
        if ($user->isSupplier() && $newStatus) {
            if ($user->supplier) {
                $user->supplier->update(['is_active' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $newStatus ? 'تم تفعيل الحساب' : 'تم تعطيل الحساب',
            'data' => ['is_active' => $newStatus],
        ]);
    }

    public function verifySupplier(User $user)
    {
        if (!$user->isSupplier()) {
            return response()->json(['success' => false, 'message' => 'المستخدم ليس مورداً'], 422);
        }

        $user->supplier->update(['is_verified' => !$user->supplier->is_verified]);

        return response()->json([
            'success' => true,
            'message' => $user->supplier->is_verified ? 'تم توثيق المورد' : 'تم إلغاء توثيق المورد',
            'data' => ['is_verified' => $user->supplier->is_verified],
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate(['type' => 'required|in:customer,supplier,admin']);

        $oldType = $user->type;
        $user->update(['type' => $request->type]);

        if ($request->type === 'supplier' && !$user->supplier) {
            Supplier::create(['user_id' => $user->id, 'company_name' => $user->store_name ?? $user->name]);
        }

        return response()->json([
            'success' => true,
            'message' => "تم تغيير الدور من {$oldType} إلى {$request->type}",
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|size:9|unique:users,phone',
            'password' => 'required|string|min:6',
            'type' => 'required|in:customer,supplier,admin',
            'store_name' => 'required_if:type,supplier|string|max:255',
            'city' => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => $request->password,
            'type' => $request->type,
            'store_name' => $request->store_name,
            'city' => $request->city,
            'is_verified' => true,
            'is_active' => true,
        ]);

        if ($user->type === 'supplier') {
            Supplier::create(['user_id' => $user->id, 'company_name' => $request->store_name]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المستخدم',
            'data' => new UserResource($user),
        ], 201);
    }

    public function destroy(User $user)
    {
        if ($user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'لا يمكن حذف مدير النظام'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف المستخدم']);
    }
}

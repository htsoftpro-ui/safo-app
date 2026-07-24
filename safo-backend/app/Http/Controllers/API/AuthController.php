<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Auth controller — handles registration, login, OTP, password reset.
 *
 * Engineering decisions:
 * - phone is the primary auth identifier (not email) — Yemeni market
 * - OTP for phone verification (SMS gateway to be configured)
 * - Sanctum tokens with explicit expiration
 * - Registration creates supplier record if type=supplier
 */
class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|size:9|unique:users,phone',
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'type' => 'sometimes|in:customer,supplier',
            'store_name' => 'required_if:type,supplier|string|max:255',
            'store_type' => 'sometimes|in:grocery,supermarket,restaurant,cafe,other',
            'city' => 'sometimes|string|max:100',
            'area' => 'sometimes|string|max:100',
        ], [
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.size' => 'رقم الهاتف يجب أن يكون 9 أرقام',
            'phone.unique' => 'رقم الهاتف مسجل بالفعل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'password.confirmed' => 'كلمتا المرور غير متطابقتين',
            'store_name.required_if' => 'اسم المتجر مطلوب للموردين',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => $request->password,
            'type' => $request->input('type', 'customer'),
            'store_name' => $request->store_name,
            'store_type' => $request->input('store_type', 'grocery'),
            'city' => $request->city,
            'area' => $request->area,
        ]);

        // Create supplier record if supplier type
        if ($user->type === 'supplier') {
            Supplier::create([
                'user_id' => $user->id,
                'company_name' => $request->store_name,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم التسجيل بنجاح',
            'data' => [
                'user' => $this->formatUser($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Login with phone + password.
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات الدخول غير صحيحة',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الحساب معطّل. يرجى التواصل مع الدعم',
            ], 403);
        }

        // Update FCM token if provided
        if ($request->has('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Revoke previous tokens (single device) — optional
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'user' => $this->formatUser($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Logout — revoke current token.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    /**
     * Get current user profile.
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->formatUser($request->user()),
        ]);
    }

    /**
     * Format user data for API response.
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'type' => $user->type,
            'store_name' => $user->store_name,
            'city' => $user->city,
            'is_verified' => $user->is_verified,
        ];
    }
}

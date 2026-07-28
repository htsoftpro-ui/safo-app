<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\Supplier;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, OtpService $otpService)
    {
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

        if ($user->type === 'supplier') {
            Supplier::create([
                'user_id' => $user->id,
                'company_name' => $request->store_name,
            ]);
        }

        // Send OTP for phone verification
        $otpResult = $otpService->sendOtp($request->phone);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم التسجيل بنجاح',
            'data' => [
                'user' => $this->formatUser($user),
                'token' => $token,
                'token_type' => 'Bearer',
                'otp_sent' => $otpResult['success'],
                'otp_message' => $otpResult['message'],
            ],
        ], 201);
    }

    public function login(LoginRequest $request)
    {
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

        if ($request->has('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        $user->update(['last_login_at' => now()]);
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

    public function logout(Request $request)
    {
        $accessToken = $request->user()->currentAccessToken();
        if ($accessToken && method_exists($accessToken, 'delete')) {
            $accessToken->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->formatUser($request->user()),
        ]);
    }

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

    /**
     * Send OTP to the authenticated user's phone.
     */
    public function sendOtp(Request $request, OtpService $otpService)
    {
        $result = $otpService->sendOtp($request->user()->phone);
        return response()->json($result, $result['success'] ? 200 : 429);
    }

    /**
     * Verify OTP code.
     */
    public function verifyOtp(Request $request, OtpService $otpService)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $result = $otpService->verifyOtp($request->user()->phone, $request->code);

        if ($result['success']) {
            $request->user()->update(['is_verified' => true]);
        }

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Request password reset via OTP.
     */
    public function forgotPassword(Request $request, OtpService $otpService)
    {
        $request->validate(['phone' => 'required|string|size:9']);

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'رقم الهاتف غير مسجل'], 404);
        }

        $result = $otpService->sendOtp($request->phone);
        return response()->json($result);
    }

    /**
     * Reset password with OTP verification.
     */
    public function resetPassword(Request $request, OtpService $otpService)
    {
        $request->validate([
            'phone' => 'required|string|size:9',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $verifyResult = $otpService->verifyOtp($request->phone, $request->code);
        if (!$verifyResult['success']) {
            return response()->json($verifyResult, 422);
        }

        $user = User::where('phone', $request->phone)->firstOrFail();
        $user->update(['password' => $request->password]);
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ]);
    }
}

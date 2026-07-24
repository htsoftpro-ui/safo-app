<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('addresses');

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $request->user()->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي',
            'data' => new UserResource($request->user()->fresh()),
        ]);
    }

    public function avatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $user = $request->user();

        if ($user->avatar) {
            $oldPath = str_replace('/storage/', '', $user->avatar);
            \Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => '/storage/' . $path]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الصورة',
            'data' => ['avatar' => $user->fresh()->avatar],
        ]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة',
            ], 422);
        }

        $user->update(['password' => $request->password]);

        // Revoke all other tokens (security best practice)
        $accessToken = $user->currentAccessToken();
        if ($accessToken && method_exists($accessToken, 'delete')) {
            $user->tokens()->where('id', '!=', $accessToken->id)->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate(['password' => 'required|string']);
        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور غير صحيحة',
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الحساب بنجاح',
        ]);
    }
}

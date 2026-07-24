<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Profile controller — manage user profile, avatar, password, account deletion.
 */
class ProfileController extends Controller
{
    /**
     * Show user profile.
     */
    public function show(Request $request)
    {
        $user = $request->user()->load('addresses');

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update profile information.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $request->user()->id,
            'city' => 'nullable|string|max:100',
            'area' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
        ]);

        $request->user()->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي',
            'data' => new UserResource($request->user()->fresh()),
        ]);
    }

    /**
     * Upload/change avatar.
     */
    public function avatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar) {
            $oldPath = str_replace('/storage/', '', $user->avatar);
            \Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => '/storage/' . $path]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الصورة',
            'data' => [
                'avatar' => $user->fresh()->avatar,
            ],
        ]);
    }

    /**
     * Change password.
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة',
            ], 422);
        }

        $user->update(['password' => $validated['password']]);

        // Revoke all other tokens (security best practice)
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ]);
    }

    /**
     * Delete account (soft delete) with password confirmation.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'كلمة المرور غير صحيحة',
            ], 422);
        }

        // Revoke all tokens
        $user->tokens()->delete();

        // Soft delete
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الحساب بنجاح',
        ]);
    }
}

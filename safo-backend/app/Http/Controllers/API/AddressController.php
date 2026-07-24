<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;

/**
 * Address controller — manage user delivery addresses.
 */
class AddressController extends Controller
{
    /**
     * List user's addresses.
     */
    public function index(Request $request)
    {
        $addresses = Address::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => AddressResource::collection($addresses),
        ]);
    }

    /**
     * Add a new address.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'address' => 'required|string|max:500',
            'city' => 'nullable|string|max:100',
            'area' => 'nullable|string|max:100',
            'building' => 'nullable|string|max:100',
            'floor' => 'nullable|string|max:50',
            'apartment' => 'nullable|string|max:50',
            'landmark' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'sometimes|boolean',
        ]);

        $validated['user_id'] = $request->user()->id;

        // If this is the first address, make it default
        $addressCount = Address::where('user_id', $request->user()->id)->count();
        if ($addressCount === 0) {
            $validated['is_default'] = true;
        }

        $address = Address::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة العنوان',
            'data' => new AddressResource($address),
        ], 201);
    }

    /**
     * Update an address.
     */
    public function update(Request $request, Address $address)
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:100',
            'address' => 'sometimes|string|max:500',
            'city' => 'nullable|string|max:100',
            'area' => 'nullable|string|max:100',
            'building' => 'nullable|string|max:100',
            'floor' => 'nullable|string|max:50',
            'apartment' => 'nullable|string|max:50',
            'landmark' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'sometimes|boolean',
        ]);

        $address->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث العنوان',
            'data' => new AddressResource($address),
        ]);
    }

    /**
     * Delete an address.
     */
    public function destroy(Request $request, Address $address)
    {
        if ($address->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // If deleted address was default, set another as default
        if ($wasDefault) {
            $nextAddress = Address::where('user_id', $request->user()->id)
                ->orderByDesc('created_at')
                ->first();

            if ($nextAddress) {
                $nextAddress->update(['is_default' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العنوان',
        ]);
    }
}

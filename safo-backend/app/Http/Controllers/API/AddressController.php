<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
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

    public function store(StoreAddressRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $addressCount = Address::where('user_id', $request->user()->id)->count();
        if ($addressCount === 0) {
            $data['is_default'] = true;
        }

        $address = Address::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة العنوان',
            'data' => new AddressResource($address),
        ], 201);
    }

    public function update(UpdateAddressRequest $request, Address $address)
    {
        $this->authorize('update', $address);
        $address->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث العنوان',
            'data' => new AddressResource($address),
        ]);
    }

    public function destroy(Request $request, Address $address)
    {
        $this->authorize('delete', $address);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $nextAddress = Address::where('user_id', $request->user()->id)
                ->orderByDesc('created_at')
                ->first();
            if ($nextAddress) {
                $nextAddress->update(['is_default' => true]);
            }
        }

        return response()->json(['success' => true, 'message' => 'تم حذف العنوان']);
    }
}

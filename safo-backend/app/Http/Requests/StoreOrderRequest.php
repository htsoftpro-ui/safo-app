<?php

namespace App\Http\Requests;

use App\Http\Requests\ApiFormRequest;

class StoreOrderRequest extends ApiFormRequest
{

    public function rules(): array
    {
        return [
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required|in:cash,credit,wallet',
            'coupon_code' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.required' => 'عنوان التوصيل مطلوب',
            'address_id.exists' => 'عنوان التوصيل غير صالح',
            'payment_method.required' => 'طريقة الدفع مطلوبة',
            'payment_method.in' => 'طريقة الدفع غير صالحة',
        ];
    }
}

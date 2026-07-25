<?php

namespace App\Http\Requests;

use App\Http\Requests\ApiFormRequest;

class RegisterRequest extends ApiFormRequest
{

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|size:9|unique:users,phone',
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'type' => 'sometimes|in:customer,supplier',
            'store_name' => 'required_if:type,supplier|string|max:255',
            'store_type' => 'sometimes|in:grocery,supermarket,restaurant,cafe,other',
            'city' => 'sometimes|string|max:100',
            'area' => 'sometimes|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.size' => 'رقم الهاتف يجب أن يكون 9 أرقام',
            'phone.unique' => 'رقم الهاتف مسجل بالفعل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'password.confirmed' => 'كلمتا المرور غير متطابقتين',
            'store_name.required_if' => 'اسم المتجر مطلوب للموردين',
        ];
    }
}

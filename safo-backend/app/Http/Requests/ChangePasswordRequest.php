<?php

namespace App\Http\Requests;

use App\Http\Requests\ApiFormRequest;

class ChangePasswordRequest extends ApiFormRequest
{

    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة',
            'password.required' => 'كلمة المرور الجديدة مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'password.confirmed' => 'كلمتا المرور غير متطابقتين',
        ];
    }
}

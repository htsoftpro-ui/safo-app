<?php

namespace App\Http\Requests;

use App\Http\Requests\ApiFormRequest;

class LoginRequest extends ApiFormRequest
{

    public function rules(): array
    {
        return [
            'phone' => 'required|string',
            'password' => 'required|string',
            'fcm_token' => 'nullable|string',
        ];
    }
}

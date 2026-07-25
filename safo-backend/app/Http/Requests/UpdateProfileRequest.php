<?php

namespace App\Http\Requests;

use App\Http\Requests\ApiFormRequest;

class UpdateProfileRequest extends ApiFormRequest
{

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $this->user()->id,
            'city' => 'nullable|string|max:100',
            'area' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
        ];
    }
}

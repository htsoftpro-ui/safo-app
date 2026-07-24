<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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

<?php

namespace App\Http\Requests;

use App\Http\Requests\ApiFormRequest;

class UpdateAddressRequest extends ApiFormRequest
{

    public function rules(): array
    {
        return [
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
        ];
    }
}

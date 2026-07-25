<?php

namespace App\Http\Requests;

use App\Http\Requests\ApiFormRequest;

class StoreCartItemRequest extends ApiFormRequest
{

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ];
    }
}

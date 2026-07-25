<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * Base API Form Request with consistent error response format.
 */
abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Override validation failed to return consistent JSON.
     */
    protected function failedValidation($validator)
    {
        throw new ValidationException($validator, response()->json([
            'success' => false,
            'message' => 'خطأ في التحقق',
            'errors' => $validator->errors(),
        ], 422));
    }
}

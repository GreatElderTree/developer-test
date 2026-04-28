<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Fixes: original had zero validation — any payload went straight into PDO queries. */
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_email'          => ['required', 'email'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.product_id'      => ['required', 'integer', 'exists:products,id'],
            'items.*.qty'             => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'              => 'Please add at least one product to your order.',
            'items.*.product_id.exists'   => 'One or more selected products are invalid.',
            'items.*.qty.min'             => 'Quantity must be at least 1.',
        ];
    }
}

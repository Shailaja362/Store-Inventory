<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:products,code'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
        ];
    }
}

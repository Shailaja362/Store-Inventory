<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_email' => ['required_without:customer_id', 'nullable', 'email', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if ($this->filled('customer_id')) {
                return;
            }

            $email = $this->input('customer_email');

            if (! $email) {
                return;
            }

            $existingCustomer = Customer::query()->where('email', $email)->first();

            if (! $existingCustomer && ! $this->filled('customer_name')) {
                $validator->errors()->add('customer_name', 'The customer name is required for a new customer.');
            }
        });
    }
}

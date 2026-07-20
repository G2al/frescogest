<?php

namespace App\Http\Requests\Auth;

use App\Enums\CustomerType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['type' => $this->input('type', CustomerType::Private->value)]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:'.implode(',', array_column(CustomerType::cases(), 'value'))],
            'company_name' => ['nullable', 'required_if:type,restaurant', 'string', 'max:255'],
            'first_name' => ['nullable', 'required_if:type,private', 'string', 'max:255'],
            'last_name' => ['nullable', 'required_if:type,private', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ];
    }
}

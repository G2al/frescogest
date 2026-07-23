<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Inserisci il tuo nome.',
            'first_name.max' => 'Il nome non può superare 255 caratteri.',
            'last_name.required' => 'Inserisci il tuo cognome.',
            'last_name.max' => 'Il cognome non può superare 255 caratteri.',
            'email.required' => 'Inserisci il tuo indirizzo email.',
            'email.email' => 'Inserisci un indirizzo email valido.',
            'email.max' => 'L’indirizzo email non può superare 255 caratteri.',
            'email.unique' => 'Esiste già un account con questo indirizzo email. Accedi oppure recupera la password.',
            'phone.required' => 'Inserisci il tuo numero di telefono.',
            'phone.max' => 'Il numero di telefono non può superare 255 caratteri.',
            'password.required' => 'Inserisci una password.',
            'password.min' => 'La password deve contenere almeno 4 caratteri.',
            'password.confirmed' => 'Le due password non coincidono.',
        ];
    }
}

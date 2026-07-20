<?php

namespace App\Http\Requests\Auth;

use App\Enums\CustomerType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => $this->input('type', CustomerType::Private->value),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'phone' => trim((string) $this->input('phone')),
        ]);
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

    public function messages(): array
    {
        return [
            'type.required' => 'Seleziona se vuoi registrarti come privato o ristoratore.',
            'type.in' => 'La tipologia di cliente selezionata non è valida.',
            'company_name.required_if' => 'Inserisci il nome dell’attività.',
            'company_name.max' => 'Il nome dell’attività non può superare 255 caratteri.',
            'first_name.required_if' => 'Inserisci il tuo nome.',
            'first_name.max' => 'Il nome non può superare 255 caratteri.',
            'last_name.required_if' => 'Inserisci il tuo cognome.',
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

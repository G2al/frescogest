<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'category' => trim((string) $this->input('category')),
            'search' => trim((string) $this->input('search')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:100'],
            'seasonal' => ['nullable', 'boolean'],
            'unit' => ['nullable', 'integer', Rule::exists('unit_of_measures', 'id')->where('active', true)],
            'min_price' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'max_price' => ['nullable', 'numeric', 'gte:min_price', 'max:99999'],
            'sort' => ['nullable', Rule::in(['relevant', 'name_asc', 'name_desc', 'price_asc', 'price_desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit.exists' => 'L’unità di misura selezionata non è disponibile.',
            'min_price.min' => 'Il prezzo minimo non può essere negativo.',
            'max_price.gte' => 'Il prezzo massimo deve essere maggiore o uguale al prezzo minimo.',
            'sort.in' => 'L’ordinamento selezionato non è valido.',
        ];
    }
}

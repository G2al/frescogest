<?php

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('employee')?->employee?->active;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:present,absent'],
            'started_at' => ['nullable', 'required_if:status,present', 'date_format:H:i'],
            'ended_at' => ['nullable', 'required_if:status,present', 'date_format:H:i'],
            'break_minutes' => ['nullable', 'integer', 'min:0', 'max:1439'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'started_at.required_if' => 'Indica l’orario di inizio.',
            'ended_at.required_if' => 'Indica l’orario di fine.',
        ];
    }
}

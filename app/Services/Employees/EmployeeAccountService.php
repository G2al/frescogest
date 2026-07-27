<?php

namespace App\Services\Employees;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EmployeeAccountService
{
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data): Employee {
            $password = (string) ($data['account_password'] ?? '');
            unset($data['account_password']);

            $data['email'] = mb_strtolower(trim((string) ($data['email'] ?? '')));
            $this->validateAccount($data, $password);

            $user = User::create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => $data['email'],
                'password' => $password,
                'active' => (bool) ($data['active'] ?? true),
                'can_access_panel' => false,
                'panel_role' => 'employee',
            ]);

            return Employee::create([...$data, 'user_id' => $user->id]);
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data): Employee {
            $password = (string) ($data['account_password'] ?? '');
            unset($data['account_password']);

            $data['email'] = mb_strtolower(trim((string) ($data['email'] ?? '')));
            $this->validateAccount($data, $password, $employee);

            $user = $employee->user;

            if (! $user && $password !== '') {
                $user = User::create([
                    'name' => trim($data['first_name'].' '.$data['last_name']),
                    'email' => $data['email'],
                    'password' => $password,
                    'active' => (bool) ($data['active'] ?? true),
                    'can_access_panel' => false,
                    'panel_role' => 'employee',
                ]);
                $data['user_id'] = $user->id;
            } elseif ($user) {
                $userData = [
                    'name' => trim($data['first_name'].' '.$data['last_name']),
                    'email' => $data['email'],
                    'active' => (bool) ($data['active'] ?? true),
                    'can_access_panel' => false,
                    'panel_role' => 'employee',
                ];

                if ($password !== '') {
                    $userData['password'] = $password;
                }

                $user->update($userData);
            }

            $employee->update($data);

            return $employee;
        });
    }

    private function validateAccount(array $data, string $password, ?Employee $employee = null): void
    {
        $userId = $employee?->user_id;

        Validator::make(
            [...$data, 'account_password' => $password],
            [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($userId),
                    Rule::unique('employees', 'email')->ignore($employee?->id),
                ],
                'phone' => ['required', 'string', 'max:50'],
                'account_password' => [
                    Rule::requiredIf(! $employee || (! $employee->user && $password === '')),
                    'nullable',
                    'string',
                    'min:8',
                ],
            ],
            [
                'email.unique' => 'Questo indirizzo email è già utilizzato.',
                'account_password.required' => 'Imposta una password per l’accesso del dipendente.',
                'account_password.min' => 'La password deve contenere almeno 8 caratteri.',
            ],
        )->validate();
    }
}

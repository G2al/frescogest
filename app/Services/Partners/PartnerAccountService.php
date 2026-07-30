<?php

namespace App\Services\Partners;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PartnerAccountService
{
    public function create(array $data): Partner
    {
        return DB::transaction(function () use ($data): Partner {
            $password = (string) ($data['account_password'] ?? '');
            unset($data['account_password'], $data['user_id']);

            $data['email'] = $this->normalizeEmail($data['email'] ?? null);
            $this->validateAccount($data, $password);

            $user = User::create($this->userData($data, $password));
            $user->forceFill(['email_verified_at' => now()])->save();

            return Partner::create([...$data, 'user_id' => $user->getKey()]);
        });
    }

    public function update(Partner $partner, array $data): Partner
    {
        return DB::transaction(function () use ($partner, $data): Partner {
            $password = (string) ($data['account_password'] ?? '');
            unset($data['account_password'], $data['user_id']);

            $data['email'] = $this->normalizeEmail($data['email'] ?? null);
            $this->validateAccount($data, $password, $partner);

            $user = $partner->user;

            if (! $user) {
                $user = User::create($this->userData($data, $password));
                $user->forceFill(['email_verified_at' => now()])->save();
                $data['user_id'] = $user->getKey();
            } else {
                $userData = $this->userData($data);

                if ($password !== '') {
                    $userData['password'] = $password;
                }

                $user->update($userData);
            }

            $partner->update($data);

            return $partner->refresh();
        });
    }

    private function userData(array $data, ?string $password = null): array
    {
        $userData = [
            'name' => trim((string) $data['name']),
            'email' => $data['email'],
            'active' => (bool) ($data['active'] ?? true),
            'can_access_panel' => true,
            'panel_role' => 'partner',
        ];

        if ($password !== null) {
            $userData['password'] = $password;
        }

        return $userData;
    }

    private function validateAccount(array $data, string $password, ?Partner $partner = null): void
    {
        Validator::make(
            [...$data, 'account_password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($partner?->user_id),
                    Rule::unique('partners', 'email')->ignore($partner?->getKey()),
                ],
                'phone' => ['nullable', 'string', 'max:50'],
                'account_password' => [
                    Rule::requiredIf(! $partner || (! $partner->user && $password === '')),
                    'nullable',
                    'string',
                    'min:8',
                ],
            ],
            [
                'email.required' => 'Inserisci l’indirizzo email usato dal partner per accedere.',
                'email.email' => 'Inserisci un indirizzo email valido.',
                'email.unique' => 'Questo indirizzo email è già utilizzato.',
                'account_password.required' => 'Imposta una password per l’accesso del partner.',
                'account_password.min' => 'La password deve contenere almeno 8 caratteri.',
            ],
        )->validate();
    }

    private function normalizeEmail(mixed $email): string
    {
        return mb_strtolower(trim((string) $email));
    }
}

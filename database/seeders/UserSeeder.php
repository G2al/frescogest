<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('frescogest.admin.email');
        $existingAdmin = User::query()->where('email', $email)->exists();
        $password = config('frescogest.admin.password');

        if (! $existingAdmin && blank($password)) {
            throw new RuntimeException('FRESCOGEST_ADMIN_PASSWORD is required to create the initial administrator.');
        }

        $attributes = [
            'name' => (string) config('frescogest.admin.name'),
            'email_verified_at' => now(),
        ];

        if (app()->isLocal() || ! $existingAdmin) {
            $attributes['password'] = Hash::make((string) $password);
        }

        $admin = User::updateOrCreate(
            ['email' => $email],
            $attributes,
        );

        $admin->forceFill([
            'active' => true,
            'can_access_panel' => true,
        ])->save();
    }
}

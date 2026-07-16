<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@frescogest.it'],
            [
                'name' => 'Amministratore FrescoGest',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $admin->forceFill([
            'active' => true,
            'can_access_panel' => true,
        ])->save();
    }
}

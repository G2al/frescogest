<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()
            ->where('email', 'admin@cerinostore.it')
            ->first()
            ?? User::query()->where('can_access_panel', true)->oldest()->first()
            ?? new User;

        $admin->forceFill([
            'name' => 'Amministratore Cerino Store',
            'email' => 'admin@cerinostore.it',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'active' => true,
            'can_access_panel' => true,
        ])->save();
    }
}

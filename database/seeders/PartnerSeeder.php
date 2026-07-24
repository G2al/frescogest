<?php

namespace Database\Seeders;

use App\Models\Partner;
use App\Models\User;
use App\Services\Partners\PartnerPriceListService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'angela@ilparadisodellafrutta.it'],
            [
                'name' => 'Angela',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'active' => true,
                'can_access_panel' => true,
                'panel_role' => 'partner',
            ],
        );

        $partner = Partner::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => 'Angela',
                'email' => $user->email,
                'active' => true,
            ],
        );

        app(PartnerPriceListService::class)->syncPartner($partner);
    }
}

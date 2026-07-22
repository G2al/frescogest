<?php

namespace Database\Seeders;

use App\Enums\StoreClosureType;
use App\Models\StoreClosureSchedule;
use Illuminate\Database\Seeder;

class StoreClosureScheduleSeeder extends Seeder
{
    public function run(): void
    {
        StoreClosureSchedule::query()->firstOrCreate(
            ['name' => 'Aggiornamento quotidiano prezzi'],
            [
                'type' => StoreClosureType::Recurring,
                'weekdays' => array_keys(StoreClosureSchedule::weekdayOptions()),
                'closure_date' => null,
                'starts_at' => '10:00',
                'ends_at' => '11:30',
                'message' => 'Antonio sta verificando il carico del giorno e aggiornando prezzi e disponibilità.',
                'active' => true,
            ],
        );
    }
}

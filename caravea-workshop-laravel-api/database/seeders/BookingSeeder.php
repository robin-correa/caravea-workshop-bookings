<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Booking::factory()->count(8)->create();
        Booking::factory()->count(16)->confirmed()->create();
        Booking::factory()->count(2)->cancelled()->create();
    }
}

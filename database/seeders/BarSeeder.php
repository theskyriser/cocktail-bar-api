<?php

namespace Database\Seeders;

use App\Models\Bar;
use App\Models\User;
use Illuminate\Database\Seeder;

class BarSeeder extends Seeder
{
    /**
     * Give every seeded user one bar to hold stock. Keyed on the user and the
     * name so that a second run finds the existing row instead of failing.
     */
    public function run(): void
    {
        foreach (User::all() as $user) {
            Bar::firstOrCreate([
                'user_id' => $user->id,
                'name' => 'Home Bar',
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Tags are personal, so every seeded user gets their own copy of the same
     * starting set rather than sharing one row.
     */
    public function run(): void
    {
        foreach (User::all() as $user) {
            foreach ($this->names() as $name) {
                Tag::firstOrCreate([
                    'user_id' => $user->id,
                    'name' => $name,
                ]);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function names(): array
    {
        return ['Favourites', 'Classic', 'Stirred', 'Shaken', 'Built', 'Aperitif', 'Long'];
    }
}

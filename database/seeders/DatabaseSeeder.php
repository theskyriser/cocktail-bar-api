<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    /**
     * The curated catalogue is seeded before anything user-scoped, because
     * stock and recipe tags both resolve against rows it creates.
     */
    public function run(): void
    {
        $this->call([
            IngredientSeeder::class,
            RecipeSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@test.com',
        ]);

        $this->call([
            BarSeeder::class,
            BarStockSeeder::class,
            TagSeeder::class,
            RecipeTagSeeder::class,
        ]);
    }
}

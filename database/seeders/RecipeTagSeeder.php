<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class RecipeTagSeeder extends Seeder
{
    /**
     * Curated recipes are shared but tags are not, so the pivot is written per
     * user against that user's own tag rows.
     */
    public function run(): void
    {
        /** @var array<string, Recipe> $recipes */
        $recipes = Recipe::query()
            ->whereNull('created_by_user_id')
            ->get()
            ->keyBy('name')
            ->all();

        foreach (User::all() as $user) {
            /** @var array<string, Tag> $tags */
            $tags = Tag::query()
                ->where('user_id', $user->id)
                ->get()
                ->keyBy('name')
                ->all();

            foreach ($this->assignments() as $name => $tagNames) {
                if (! isset($recipes[$name])) {
                    continue;
                }

                $ids = [];

                foreach ($tagNames as $tagName) {
                    if (isset($tags[$tagName])) {
                        $ids[] = $tags[$tagName]->id;
                    }
                }

                $recipes[$name]->tags()->syncWithoutDetaching($ids);
            }
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function assignments(): array
    {
        return [
            'Negroni' => ['Classic', 'Stirred', 'Aperitif', 'Favourites'],
            'Daiquiri' => ['Classic', 'Shaken', 'Favourites'],
            'Old Fashioned' => ['Classic', 'Stirred', 'Favourites'],
            'Manhattan' => ['Classic', 'Stirred'],
            'Margarita' => ['Classic', 'Shaken'],
            'Whiskey Sour' => ['Classic', 'Shaken'],
            'Mojito' => ['Built', 'Long'],
            'Gin and Tonic' => ['Built', 'Long'],
            'Americano' => ['Built', 'Long', 'Aperitif'],
        ];
    }
}

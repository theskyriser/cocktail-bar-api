<?php

namespace Database\Seeders;

use App\Models\Bar;
use App\Models\Ingredient;
use Illuminate\Database\Seeder;

class BarStockSeeder extends Seeder
{
    /**
     * A deliberately partial shelf, so the matcher has something to say: four
     * recipes land ready, the Manhattan only via the rye-to-bourbon swap, and
     * four sit one ingredient short. Amounts are in the ingredient's own unit —
     * millilitres for a bottle, dashes for bitters, whole fruit for a lime.
     */
    public function run(): void
    {
        /** @var array<string, Ingredient> $bySlug */
        $bySlug = Ingredient::query()
            ->whereNull('created_by_user_id')
            ->get()
            ->keyBy('slug')
            ->all();

        foreach (Bar::all() as $bar) {
            foreach ($this->shelf() as $slug => [$amount, $bottleSize]) {
                if (! isset($bySlug[$slug])) {
                    continue;
                }

                $bar->stock()->updateOrCreate(
                    ['ingredient_id' => $bySlug[$slug]->id],
                    ['amount' => $amount, 'bottle_size' => $bottleSize],
                );
            }
        }
    }

    /**
     * @return array<string, array{0: int, 1: int|null}>
     */
    private function shelf(): array
    {
        return [
            'london-dry-gin' => [520, 700],
            'bourbon' => [300, 700],
            'dark-rum' => [480, 700],
            'campari' => [400, 700],
            'sweet-vermouth' => [180, 750],
            'triple-sec' => [250, 700],
            'angostura' => [60, 100],
            'orange-bitters' => [80, 100],
            'simple-syrup' => [250, 500],
            'lime-juice' => [90, 250],
            'tonic-water' => [1000, 1000],
            'soda-water' => [1500, 1500],
            'lime' => [3, null],
            'lemon' => [2, null],
            'orange' => [2, null],
            'mint' => [1, null],
            'orange-peel' => [4, null],
            'lime-wheel' => [3, null],
            'cocktail-cherry' => [10, null],
        ];
    }
}

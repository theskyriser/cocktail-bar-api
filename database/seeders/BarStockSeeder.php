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
     *
     * A shelf holds brands, so each row names a product where the catalogue
     * has one. Where it does not — a lime, mint, homemade syrup — the style is
     * the most specific row there is, and the shelf names that instead. The
     * styles represented are the same ones as before, so what is ready, what
     * needs a swap and what is one short all stay put.
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
            'tanqueray' => [520, 700],
            'buffalo-trace' => [300, 700],
            'appleton-signature' => [480, 700],
            'campari' => [400, 700],
            'carpano-antica' => [180, 750],
            'cointreau' => [250, 700],
            'angostura' => [60, 100],
            'regans-orange-bitters' => [80, 100],
            'fever-tree-tonic' => [1000, 1000],
            'fever-tree-soda' => [1500, 1500],
            'luxardo-cherries' => [10, null],
            'simple-syrup' => [250, 500],
            'lime-juice' => [90, 250],
            'lime' => [3, null],
            'lemon' => [2, null],
            'orange' => [2, null],
            'mint' => [1, null],
            'orange-peel' => [4, null],
            'lime-wheel' => [3, null],
        ];
    }
}

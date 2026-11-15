<?php

namespace Database\Seeders;

use App\Enums\RecipeItemRole;
use App\Enums\SubstitutionRule;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    /**
     * Amounts are held in the ingredient's own `default_unit`, so a bitters
     * line counts dashes and a garnish line counts pieces. A null amount is an
     * unmeasured line — a garnish, or a top-up to fill the glass.
     */
    public function run(): void
    {
        /** @var array<string, Ingredient> $bySlug */
        $bySlug = Ingredient::query()
            ->whereNull('created_by_user_id')
            ->get()
            ->keyBy('slug')
            ->all();

        foreach ($this->recipes() as $definition) {
            $recipe = Recipe::updateOrCreate(
                ['name' => $definition['name'], 'created_by_user_id' => null],
                ['description' => $definition['description'], 'method' => $definition['method']],
            );

            $position = 0;

            foreach ($definition['items'] as $item) {
                if (! isset($bySlug[$item['ingredient']])) {
                    continue;
                }

                $position++;

                $recipe->items()->updateOrCreate(
                    ['ingredient_id' => $bySlug[$item['ingredient']]->id],
                    [
                        'amount' => $item['amount'],
                        'role' => $item['role'],
                        'sub_rule' => $item['sub_rule'] ?? SubstitutionRule::Exact,
                        'is_optional' => $item['is_optional'] ?? false,
                        'position' => $position,
                    ],
                );
            }
        }
    }

    /**
     * @return array<int, array{name: string, description: string, method: string, items: array<int, array{ingredient: string, amount: int|null, role: RecipeItemRole, sub_rule?: SubstitutionRule, is_optional?: bool}>}>
     */
    private function recipes(): array
    {
        return [
            [
                'name' => 'Negroni',
                'description' => 'Equal parts, stirred, and bitter enough to wake the palate up.',
                'method' => 'Stir all three with ice until well chilled. Strain over a large cube and twist the orange peel over the surface.',
                'items' => [
                    ['ingredient' => 'gin', 'amount' => 30, 'role' => RecipeItemRole::BaseSpirit],
                    ['ingredient' => 'campari', 'amount' => 30, 'role' => RecipeItemRole::Bitters, 'sub_rule' => SubstitutionRule::AnyInGroup],
                    ['ingredient' => 'sweet-vermouth', 'amount' => 30, 'role' => RecipeItemRole::Modifier],
                    ['ingredient' => 'orange-peel', 'amount' => 1, 'role' => RecipeItemRole::Garnish, 'is_optional' => true],
                ],
            ],
            [
                'name' => 'Daiquiri',
                'description' => 'Rum, lime and sugar in the proportions that make the case for all three.',
                'method' => 'Shake hard with cubed ice for ten seconds and double-strain into a chilled coupe.',
                'items' => [
                    ['ingredient' => 'white-rum', 'amount' => 60, 'role' => RecipeItemRole::BaseSpirit],
                    ['ingredient' => 'lime-juice', 'amount' => 30, 'role' => RecipeItemRole::Modifier],
                    ['ingredient' => 'simple-syrup', 'amount' => 15, 'role' => RecipeItemRole::Sweetener],
                ],
            ],
            [
                'name' => 'Old Fashioned',
                'description' => 'Whiskey, sugar, bitters. The drink every other cocktail is measured against.',
                'method' => 'Stir with ice until the sugar is dissolved and the glass is frosted. Serve over one large cube with an expressed orange peel.',
                'items' => [
                    ['ingredient' => 'bourbon', 'amount' => 60, 'role' => RecipeItemRole::BaseSpirit, 'sub_rule' => SubstitutionRule::AnyInGroup],
                    ['ingredient' => 'simple-syrup', 'amount' => 10, 'role' => RecipeItemRole::Sweetener],
                    ['ingredient' => 'angostura', 'amount' => 2, 'role' => RecipeItemRole::Bitters],
                    ['ingredient' => 'orange-peel', 'amount' => 1, 'role' => RecipeItemRole::Garnish, 'is_optional' => true],
                ],
            ],
            [
                'name' => 'Manhattan',
                'description' => 'Rye and sweet vermouth, stirred down and served up.',
                'method' => 'Stir with ice until very cold, strain into a chilled coupe and drop in the cherry.',
                'items' => [
                    ['ingredient' => 'rye', 'amount' => 60, 'role' => RecipeItemRole::BaseSpirit, 'sub_rule' => SubstitutionRule::AnyInGroup],
                    ['ingredient' => 'sweet-vermouth', 'amount' => 30, 'role' => RecipeItemRole::Modifier],
                    ['ingredient' => 'angostura', 'amount' => 2, 'role' => RecipeItemRole::Bitters],
                    ['ingredient' => 'cocktail-cherry', 'amount' => 1, 'role' => RecipeItemRole::Garnish, 'is_optional' => true],
                ],
            ],
            [
                'name' => 'Margarita',
                'description' => 'Tequila sharpened with lime and rounded off with orange liqueur.',
                'method' => 'Shake with ice and strain into a chilled glass. Salt the rim on one side only, so the drinker gets to choose.',
                'items' => [
                    ['ingredient' => 'blanco-tequila', 'amount' => 50, 'role' => RecipeItemRole::BaseSpirit],
                    ['ingredient' => 'triple-sec', 'amount' => 20, 'role' => RecipeItemRole::Modifier],
                    ['ingredient' => 'lime-juice', 'amount' => 25, 'role' => RecipeItemRole::Modifier],
                    ['ingredient' => 'lime-wheel', 'amount' => 1, 'role' => RecipeItemRole::Garnish, 'is_optional' => true],
                ],
            ],
            [
                'name' => 'Whiskey Sour',
                'description' => 'The sour template: spirit, citrus, sugar, shaken cold.',
                'method' => 'Shake with ice until the tin frosts and strain into a chilled coupe.',
                'items' => [
                    ['ingredient' => 'bourbon', 'amount' => 60, 'role' => RecipeItemRole::BaseSpirit, 'sub_rule' => SubstitutionRule::AnyInGroup],
                    ['ingredient' => 'lemon-juice', 'amount' => 30, 'role' => RecipeItemRole::Modifier],
                    ['ingredient' => 'simple-syrup', 'amount' => 15, 'role' => RecipeItemRole::Sweetener],
                ],
            ],
            [
                'name' => 'Mojito',
                'description' => 'Rum and lime lengthened with soda, with mint doing the aromatic work.',
                'method' => 'Press the mint gently against the glass rather than muddling it to pulp. Add rum, lime and syrup, fill with crushed ice and top with soda.',
                'items' => [
                    ['ingredient' => 'white-rum', 'amount' => 50, 'role' => RecipeItemRole::BaseSpirit],
                    ['ingredient' => 'lime-juice', 'amount' => 25, 'role' => RecipeItemRole::Modifier],
                    ['ingredient' => 'simple-syrup', 'amount' => 15, 'role' => RecipeItemRole::Sweetener],
                    ['ingredient' => 'mint', 'amount' => 1, 'role' => RecipeItemRole::Garnish],
                    ['ingredient' => 'soda-water', 'amount' => null, 'role' => RecipeItemRole::Modifier],
                ],
            ],
            [
                'name' => 'Gin and Tonic',
                'description' => 'Two ingredients, so both of them have to be good.',
                'method' => 'Build over plenty of ice in a chilled highball, add tonic poured down a bar spoon, and stir once.',
                'items' => [
                    ['ingredient' => 'gin', 'amount' => 50, 'role' => RecipeItemRole::BaseSpirit],
                    ['ingredient' => 'tonic-water', 'amount' => null, 'role' => RecipeItemRole::Modifier],
                    ['ingredient' => 'lime-wheel', 'amount' => 1, 'role' => RecipeItemRole::Garnish, 'is_optional' => true],
                ],
            ],
            [
                'name' => 'Americano',
                'description' => 'The Negroni with the gin swapped for soda, and lower for it in every sense.',
                'method' => 'Build over ice in a highball, top with soda water and garnish with orange peel.',
                'items' => [
                    ['ingredient' => 'campari', 'amount' => 30, 'role' => RecipeItemRole::Bitters, 'sub_rule' => SubstitutionRule::AnyInGroup],
                    ['ingredient' => 'sweet-vermouth', 'amount' => 30, 'role' => RecipeItemRole::Modifier],
                    ['ingredient' => 'soda-water', 'amount' => null, 'role' => RecipeItemRole::Modifier],
                    ['ingredient' => 'orange-peel', 'amount' => 1, 'role' => RecipeItemRole::Garnish, 'is_optional' => true],
                ],
            ],
        ];
    }
}

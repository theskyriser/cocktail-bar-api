<?php

namespace Tests\Feature;

use App\Enums\IngredientKind;
use App\Enums\IngredientType;
use App\Enums\MeasurementUnit;
use App\Enums\SubstituteQuality;
use App\Models\Ingredient;
use App\Models\IngredientSubstitute;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IngredientSubstituteTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_the_pivot_model_onto_the_table_the_migration_creates(): void
    {
        $table = (new IngredientSubstitute)->getTable();

        $this->assertSame('ingredient_substitute', $table);
        $this->assertTrue(Schema::hasTable($table));
        $this->assertSame(0, IngredientSubstitute::count());
    }

    public function test_it_points_both_substitution_relations_at_that_same_table(): void
    {
        $ingredient = $this->ingredientNamed('Rye');

        $this->assertSame((new IngredientSubstitute)->getTable(), $ingredient->substitutes()->getTable());
        $this->assertSame((new IngredientSubstitute)->getTable(), $ingredient->substituteFor()->getTable());
    }

    public function test_it_reads_a_substitution_written_through_the_relation_back_off_the_model(): void
    {
        $rye = $this->ingredientNamed('Rye');
        $bourbon = $this->ingredientNamed('Bourbon');

        $rye->substitutes()->attach($bourbon, [
            'quality' => SubstituteQuality::Acceptable,
            'note' => 'Sweeter, less spice.',
        ]);

        $substitution = IngredientSubstitute::query()
            ->where('from_ingredient_id', $rye->id)
            ->sole();

        $this->assertSame($bourbon->id, $substitution->to_ingredient_id);
        $this->assertSame(SubstituteQuality::Acceptable, $substitution->quality);
        $this->assertSame('Sweeter, less spice.', $substitution->note);
    }

    public function test_it_reads_a_substitution_written_through_the_model_back_off_the_relation(): void
    {
        $cordial = $this->ingredientNamed('Lime Cordial');
        $juice = $this->ingredientNamed('Lime Juice');

        IngredientSubstitute::create([
            'from_ingredient_id' => $cordial->id,
            'to_ingredient_id' => $juice->id,
            'quality' => SubstituteQuality::Poor,
        ]);

        $this->assertSame(['Lime Juice'], $cordial->substitutes->pluck('name')->all());
        $this->assertSame(['Lime Cordial'], $juice->substituteFor->pluck('name')->all());
        $this->assertSame(SubstituteQuality::Poor, $cordial->substitutes->first()->pivot->quality);
    }

    public function test_it_refuses_the_same_substitution_twice(): void
    {
        $rye = $this->ingredientNamed('Rye');
        $bourbon = $this->ingredientNamed('Bourbon');

        IngredientSubstitute::create([
            'from_ingredient_id' => $rye->id,
            'to_ingredient_id' => $bourbon->id,
            'quality' => SubstituteQuality::Acceptable,
        ]);

        $this->expectException(QueryException::class);

        IngredientSubstitute::create([
            'from_ingredient_id' => $rye->id,
            'to_ingredient_id' => $bourbon->id,
            'quality' => SubstituteQuality::Good,
        ]);
    }

    public function test_it_discards_the_substitution_when_either_ingredient_goes(): void
    {
        $rye = $this->ingredientNamed('Rye');
        $bourbon = $this->ingredientNamed('Bourbon');

        $rye->substitutes()->attach($bourbon, ['quality' => SubstituteQuality::Acceptable]);

        $bourbon->delete();

        $this->assertSame(0, IngredientSubstitute::count());
    }

    protected function ingredientNamed(string $name): Ingredient
    {
        return Ingredient::create([
            'slug' => str($name)->slug()->value(),
            'name' => $name,
            'kind' => IngredientKind::Product,
            'type' => IngredientType::Spirit,
            'is_alcoholic' => true,
            'default_unit' => MeasurementUnit::Millilitre,
        ]);
    }
}

<?php

namespace App\Enums;

enum IngredientType: string
{
    case Spirit = 'spirit';
    case Liqueur = 'liqueur';
    case Fortified = 'fortified';
    case Bitters = 'bitters';
    case Fresh = 'fresh';
    case Juice = 'juice';
    case Syrup = 'syrup';
    case Mixer = 'mixer';
    case Garnish = 'garnish';

    public function label(): string
    {
        return match ($this) {
            self::Spirit => 'Spirits',
            self::Liqueur => 'Liqueurs',
            self::Fortified => 'Fortified Wines',
            self::Bitters => 'Bitters',
            self::Fresh => 'Fresh',
            self::Juice => 'Juices',
            self::Syrup => 'Syrups',
            self::Mixer => 'Mixers',
            self::Garnish => 'Garnishs',
        };
    }
}

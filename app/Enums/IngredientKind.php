<?php

namespace App\Enums;

enum IngredientKind: string
{
    case Category = 'category';
    case Style = 'style';
    case Product = 'product';
}

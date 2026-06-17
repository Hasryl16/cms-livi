<?php

namespace App\Filament\Admin\Resources;

use Filament\Resources\Resource;
use UnitEnum;

abstract class BaseResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 1;
}
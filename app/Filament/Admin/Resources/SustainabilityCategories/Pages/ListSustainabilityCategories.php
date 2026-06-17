<?php

namespace App\Filament\Admin\Resources\SustainabilityCategories\Pages;

use App\Filament\Admin\Resources\SustainabilityCategories\SustainabilityCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSustainabilityCategories extends ListRecords
{
    protected static string $resource = SustainabilityCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\SustainabilityCategories\Pages;

use App\Filament\Admin\Resources\SustainabilityCategories\SustainabilityCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSustainabilityCategory extends CreateRecord
{
    protected static string $resource = SustainabilityCategoryResource::class;
    
    // Redirect to the index page after creating a new record
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    
}

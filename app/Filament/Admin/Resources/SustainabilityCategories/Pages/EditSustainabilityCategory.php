<?php

namespace App\Filament\Admin\Resources\SustainabilityCategories\Pages;

use App\Filament\Admin\Resources\SustainabilityCategories\SustainabilityCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSustainabilityCategory extends EditRecord
{
    protected static string $resource = SustainabilityCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    // Redirect to the index page after updating the record
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

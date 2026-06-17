<?php

namespace App\Filament\Admin\Resources\SustainabilityContents\Pages;

use App\Filament\Admin\Resources\SustainabilityContents\SustainabilityContentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSustainabilityContent extends EditRecord
{
    protected static string $resource = SustainabilityContentResource::class;

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

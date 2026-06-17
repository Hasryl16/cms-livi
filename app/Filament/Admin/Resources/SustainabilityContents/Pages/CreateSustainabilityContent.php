<?php

namespace App\Filament\Admin\Resources\SustainabilityContents\Pages;

use App\Filament\Admin\Resources\SustainabilityContents\SustainabilityContentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSustainabilityContent extends CreateRecord
{
    protected static string $resource = SustainabilityContentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] ??= Auth::id();

        return $data;
    }
    // Redirect to the index page after creating a new record
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

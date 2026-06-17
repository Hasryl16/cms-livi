<?php

namespace App\Filament\Admin\Resources\SustainabilityContents\Pages;

use App\Filament\Admin\Resources\SustainabilityContents\SustainabilityContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSustainabilityContents extends ListRecords
{
    protected static string $resource = SustainabilityContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

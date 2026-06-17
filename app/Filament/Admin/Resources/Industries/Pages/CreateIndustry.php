<?php

namespace App\Filament\Admin\Resources\Industries\Pages;

use App\Filament\Admin\Resources\Industries\IndustryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIndustry extends CreateRecord
{
    protected static string $resource = IndustryResource::class;

    //balik ke index setelah create
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

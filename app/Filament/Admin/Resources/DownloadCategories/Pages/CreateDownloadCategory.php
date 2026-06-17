<?php

namespace App\Filament\Admin\Resources\DownloadCategories\Pages;

use App\Filament\Admin\Resources\DownloadCategories\DownloadCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDownloadCategory extends CreateRecord
{
    protected static string $resource = DownloadCategoryResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

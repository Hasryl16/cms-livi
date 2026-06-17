<?php

namespace App\Filament\Admin\Resources\DownloadCategories\Pages;

use App\Filament\Admin\Resources\DownloadCategories\DownloadCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDownloadCategory extends EditRecord
{
    protected static string $resource = DownloadCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


}

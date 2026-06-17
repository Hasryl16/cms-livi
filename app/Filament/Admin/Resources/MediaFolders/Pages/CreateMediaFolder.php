<?php

namespace App\Filament\Admin\Resources\MediaFolders\Pages;

use App\Filament\Admin\Resources\MediaFolders\MediaFolderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMediaFolder extends CreateRecord
{
    protected static string $resource = MediaFolderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
    
     //redirect to index page after update
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

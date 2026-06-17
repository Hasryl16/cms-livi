<?php

namespace App\Filament\Admin\Resources\MediaFolders\Pages;

use App\Filament\Admin\Resources\MediaFolders\MediaFolderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMediaFolder extends EditRecord
{
    protected static string $resource = MediaFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    //redirect to index page after update
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

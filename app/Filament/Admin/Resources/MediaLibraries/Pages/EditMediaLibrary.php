<?php

namespace App\Filament\Admin\Resources\MediaLibraries\Pages;

use App\Filament\Admin\Resources\MediaLibraries\MediaLibraryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMediaLibrary extends EditRecord
{
    protected static string $resource = MediaLibraryResource::class;

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

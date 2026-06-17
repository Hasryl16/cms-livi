<?php

namespace App\Filament\Admin\Resources\MediaFolders\Pages;

use App\Filament\Admin\Resources\MediaFolders\MediaFolderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMediaFolders extends ListRecords
{
    protected static string $resource = MediaFolderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\MediaLibraries\Pages;

use App\Filament\Admin\Resources\MediaLibraries\MediaLibraryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMediaLibraries extends ListRecords
{
    protected static string $resource = MediaLibraryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

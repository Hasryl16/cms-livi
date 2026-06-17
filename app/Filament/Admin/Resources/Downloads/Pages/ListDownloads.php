<?php

namespace App\Filament\Admin\Resources\Downloads\Pages;

use App\Filament\Admin\Resources\Downloads\DownloadResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDownloads extends ListRecords
{
    protected static string $resource = DownloadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
